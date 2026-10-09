<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use DateTimeImmutable;
use LogicException;
use Netresearch\HttpGuard\ClockInterface;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\NrHttpGuard\Command\PolicyCheckCommand;
use Netresearch\NrHttpGuard\Configuration\ConfigurationLoader;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticBootState;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticsService;
use Netresearch\NrHttpGuard\Diagnostics\LegacyConfigurationInventory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

final class AdapterInputShapeTest extends TestCase
{
    private mixed $original;

    protected function setUp(): void
    {
        $this->original = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->original === null) {
            unset($GLOBALS['TYPO3_CONF_VARS']);
        } else {
            $GLOBALS['TYPO3_CONF_VARS'] = $this->original;
        }
    }

    #[DataProvider('invalidConfiguration')]
    public function testMalformedConfigurationHasAControlledReason(mixed $configuration): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $configuration;
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        (new ConfigurationLoader())->load();
    }

    public static function invalidConfiguration(): iterable
    {
        yield 'root scalar' => ['invalid'];
        yield 'EXTCONF scalar' => [['EXTCONF' => 'invalid']];
        yield 'EXTCONF false' => [['EXTCONF' => false]];
        yield 'extension scalar' => [['EXTCONF' => ['nr_http_guard' => false]]];
    }

    #[DataProvider('invalidHttp')]
    public function testLegacyDiagnosticsReportMalformedHttpAsConfigurationFailure(mixed $configuration): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $configuration;
        $result                     = $this->service()->legacyReport();
        self::assertSame(3, $result->exitCode);
        self::assertSame('configuration_invalid', $result->payload['reasonCode']);
        self::assertFalse($result->payload['httpSent']);
    }

    public static function invalidHttp(): iterable
    {
        yield 'root scalar' => ['invalid'];
        yield 'HTTP scalar' => [['HTTP' => 'invalid']];
        yield 'numeric HTTP key' => [['HTTP' => [0 => 'invalid']]];
    }

    #[DataProvider('invalidHttp')]
    public function testDiagnosticDenialSurvivesMalformedHttpContainers(mixed $configuration): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $configuration;
        $boot                       = new DiagnosticBootState();
        $boot->recordAndBlock('configuration_invalid');
        self::assertSame('configuration_invalid', $boot->failureReason);
        $first   = array_values($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'])[0];
        $handler = $first(
            static function (): never {
                throw new LogicException('Unprotected leaf reached');
            },
        );
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        $handler(new \GuzzleHttp\Psr7\Request('GET', 'https://example.test'), []);
    }

    public function testMalformedArgvCannotBecomeADiagnosticInvocation(): void
    {
        self::assertFalse((new DiagnosticBootState())->isDiagnosticInvocation(['typo3', ['http-guard:doctor']]));
    }

    #[DataProvider('invalidConsoleInput')]
    public function testMalformedConsoleInputIsRejectedBeforePolicyEvaluation(array $input): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        $tester                     = new CommandTester(new PolicyCheckCommand($this->service()));
        $this->expectException(InvalidArgumentException::class);
        $tester->execute($input);
    }

    public static function invalidConsoleInput(): iterable
    {
        yield 'URL array' => [['url' => ['https://example.test']]];
        yield 'endpoint array' => [['url' => 'https://example.test', '--endpoint' => ['erp']]];
        yield 'no-DNS scalar' => [['url' => 'https://example.test', '--no-dns' => 'not-a-boolean']];
    }

    private function service(): DiagnosticsService
    {
        $clock = new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-10-09T12:00:00Z');
            }

            public function monotonic(): float
            {
                return 0.0;
            }
        };
        $forbidden = static fn (): never => throw new LogicException('Unexpected policy evaluation');

        return new DiagnosticsService(
            new ConfigurationLoader(),
            new DiagnosticBootState(),
            new LegacyConfigurationInventory(),
            $forbidden,
            $forbidden,
            $forbidden,
            $clock,
        );
    }

    #[DataProvider('nonListArguments')]
    public function testNonListStringArgumentsCannotBecomeADiagnosticInvocation(array $arguments): void
    {
        self::assertFalse((new DiagnosticBootState())->isDiagnosticInvocation($arguments));
    }

    /** @return iterable<string,array{array<array-key,string>}> */
    public static function nonListArguments(): iterable
    {
        yield 'associative keys' => [['program' => 'typo3', 'command' => 'http-guard:doctor']];
        yield 'sparse numeric keys' => [[0 => 'typo3', 2 => 'http-guard:doctor']];
    }

    public function testScalarServerArgumentsPreserveTheOriginalBootstrapPolicyFailure(): void
    {
        $existed         = array_key_exists('argv', $_SERVER);
        $original        = $_SERVER['argv'] ?? null;
        $failure         = new PolicyException('configuration_invalid');
        $state           = new DiagnosticBootState();
        $listener        = new \Netresearch\NrHttpGuard\EventListener\RegisterGuardListener(static fn (): never => throw $failure, $state);
        $_SERVER['argv'] = 'synthetic scalar argv';
        try {
            try {
                $listener(new \TYPO3\CMS\Core\Core\Event\BootCompletedEvent(false));
                self::fail('Malformed argv enabled diagnostic recovery');
            } catch (PolicyException $actual) {
                self::assertSame($failure, $actual);
            }
            self::assertNull($state->failureReason);
        } finally {
            if ($existed) {
                $_SERVER['argv'] = $original;
            } else {
                unset($_SERVER['argv']);
            }
        }
    }
}
