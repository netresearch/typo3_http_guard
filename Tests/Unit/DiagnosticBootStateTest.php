<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticBootState;
use Netresearch\NrHttpGuard\EventListener\RegisterGuardListener;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;

final class DiagnosticBootStateTest extends TestCase
{
    private mixed $globals;
    private array $argv;

    protected function setUp(): void
    {
        $this->globals = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        $this->argv    = $_SERVER['argv'];
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->argv;
        if ($this->globals === null) {
            unset($GLOBALS['TYPO3_CONF_VARS']);
        } else {
            $GLOBALS['TYPO3_CONF_VARS'] = $this->globals;
        }
    }

    public function testOnlyTheFourPrimaryDiagnosticCommandsAreEligible(): void
    {
        $state = new DiagnosticBootState();
        foreach (['doctor', 'config-check', 'policy-check', 'legacy-report'] as $command) {
            self::assertTrue($state->isDiagnosticInvocation(['typo3', '--quiet', 'http-guard:' . $command]));
        }
        self::assertFalse($state->isDiagnosticInvocation(['typo3', 'cache:flush', 'http-guard:doctor']));
        self::assertFalse($state->isDiagnosticInvocation(['typo3', 'http-guard:unknown']));
    }

    public function testDiagnosticFailureLeavesAFirstDenialBeforeAnyLeaf(): void
    {
        $_SERVER['argv'] = ['typo3', '--no-interaction', 'http-guard:doctor'];
        $state           = new DiagnosticBootState();
        $listener        = new RegisterGuardListener(
            static function (): never {
                throw new PolicyException('configuration_invalid');
            },
            $state,
        );
        $listener(new BootCompletedEvent(false));
        self::assertSame('configuration_invalid', $state->failureReason);
        $leafCalls = 0;
        $first     = array_values($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'])[0];
        $handler   = $first(
            static function () use (&$leafCalls): void {
                ++$leafCalls;
            },
        );
        try {
            $handler(new Request('GET', 'http://guard.test/a'), []);
            self::fail('expected first denial');
        } catch (PolicyException $exception) {
            self::assertSame('configuration_invalid', $exception->reasonCode());
        }
        self::assertSame(0, $leafCalls);
    }

    public function testOrdinaryBootstrapStillThrows(): void
    {
        $_SERVER['argv'] = ['typo3', 'cache:flush'];
        $listener        = new RegisterGuardListener(
            static function (): never {
                throw new PolicyException('configuration_invalid');
            },
            new DiagnosticBootState(),
        );
        $this->expectException(PolicyException::class);
        $listener(new BootCompletedEvent(false));
    }

    public function testPolicyFailuresAreNotAcceptedAsDiagnosticBootstrapFailures(): void
    {
        $_SERVER['argv'] = ['typo3', 'http-guard:doctor'];
        $listener        = new RegisterGuardListener(
            static function (): never {
                throw new PolicyException('grant_invalid');
            },
            new DiagnosticBootState(),
        );
        $this->expectException(PolicyException::class);
        $listener(new BootCompletedEvent(false));
    }
}
