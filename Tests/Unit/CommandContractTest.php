<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use JsonException;
use Netresearch\NrHttpGuard\Command\AbstractGuardCommand;
use Netresearch\NrHttpGuard\Command\ConfigCheckCommand;
use Netresearch\NrHttpGuard\Command\DoctorCommand;
use Netresearch\NrHttpGuard\Command\LegacyReportCommand;
use Netresearch\NrHttpGuard\Command\PolicyCheckCommand;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticResult;
use Netresearch\NrHttpGuard\Tests\Fixtures\AdapterRuntimeFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class CommandContractTest extends TestCase
{
    private ?AdapterRuntimeFixture $fixture = null;

    protected function tearDown(): void
    {
        $this->fixture?->restore();
    }

    #[DataProvider('commands')]
    public function testCommandsKeepExactServicePayloadAndExitCode(
        string $kind,
        string $mode,
        ?string $failure,
    ): void {
        $this->fixture = new AdapterRuntimeFixture(['mode' => $mode]);
        if ($failure !== null) {
            $this->fixture->boot->recordAndBlock($failure);
        }
        $diagnostics               = $this->fixture->diagnostics;
        [$command, $result, $name] = match ($kind) {
            'config' => [new ConfigCheckCommand($diagnostics), $diagnostics->configCheck(), 'http-guard:config-check'],
            'doctor' => [new DoctorCommand($diagnostics), $diagnostics->doctor(), 'http-guard:doctor'],
            'legacy' => [new LegacyReportCommand($diagnostics), $diagnostics->legacyReport(), 'http-guard:legacy-report'],
        };
        self::assertSame($name, $command->getName());
        $tester = new CommandTester($command);
        self::assertSame($result->exitCode, $tester->execute([]));
        self::assertSame(
            json_encode($result->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            $tester->getDisplay(),
        );
    }

    /** @return iterable<string,array{string,string,?string}> */
    public static function commands(): iterable
    {
        yield 'valid config' => ['config', 'enforce', null];
        yield 'failed config' => ['config', 'enforce', 'configuration_invalid'];
        yield 'enforce doctor' => ['doctor', 'enforce', null];
        yield 'observe doctor' => ['doctor', 'observe', null];
        yield 'disabled doctor' => ['doctor', 'disabled', null];
        yield 'failed doctor' => ['doctor', 'enforce', 'transport_unsupported'];
        yield 'legacy inventory' => ['legacy', 'enforce', null];
        yield 'failed legacy inventory' => ['legacy', 'enforce', 'configuration_invalid'];
    }

    #[DataProvider('policies')]
    public function testPolicyCommandParsesEndpointAndNoDnsWithoutSending(
        string $url,
        ?string $endpoint,
        int $exit,
        ?string $reason,
    ): void {
        $this->fixture = new AdapterRuntimeFixture(['endpoints' => ['erp' => AdapterRuntimeFixture::endpoint()]]);
        $command       = new PolicyCheckCommand($this->fixture->diagnostics);
        self::assertSame('http-guard:policy-check', $command->getName());
        self::assertTrue($command->getDefinition()->getArgument('url')->isRequired());
        self::assertTrue($command->getDefinition()->getOption('endpoint')->isValueRequired());
        self::assertFalse($command->getDefinition()->getOption('no-dns')->acceptValue());
        $tester = new CommandTester($command);
        $input  = ['url' => $url, '--no-dns' => true];
        if ($endpoint !== null) {
            $input['--endpoint'] = $endpoint;
        }
        self::assertSame($exit, $tester->execute($input));
        $payload = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($reason, $payload['reasonCode']);
        self::assertFalse($payload['httpSent']);
        self::assertTrue($payload['diagnosticOnly']);
        self::assertSame($this->fixture->diagnostics->policyCheck($url, $endpoint, true)->payload, $payload);
    }

    /** @return iterable<string,array{string,?string,int,?string}> */
    public static function policies(): iterable
    {
        yield 'private scoped endpoint' => ['https://erp.test/', 'erp', 0, null];
        yield 'private address forbidden' => ['https://127.0.0.1/', null, 2, 'address_forbidden'];
        yield 'offline unverified DNS' => ['https://never-resolve.invalid/', null, 4, 'resolution_unverified'];
        yield 'unknown endpoint' => ['https://erp.test/', 'missing', 2, 'grant_invalid'];
    }

    public function testMissingUrlRemainsARequiredConsoleArgument(): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $tester        = new CommandTester(new PolicyCheckCommand($this->fixture->diagnostics));
        $this->expectException(RuntimeException::class);
        $tester->execute(['--no-dns' => true]);
    }

    public function testDiagnosticResultWriterPreservesExitCodeAndUnescapedUrls(): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $command       = new class ($this->fixture->diagnostics) extends AbstractGuardCommand {
            public function render(DiagnosticResult $result, OutputInterface $output): int
            {
                return $this->result($result, $output);
            }
        };
        $output = new BufferedOutput();
        self::assertSame(7, $command->render(new DiagnosticResult(['link' => 'https://example.test/a/b'], 7), $output));
        self::assertSame('{"link":"https://example.test/a/b"}' . PHP_EOL, $output->fetch());
    }

    public function testInvalidJsonFailsBeforeAnyPartialOutput(): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $command       = new class ($this->fixture->diagnostics) extends AbstractGuardCommand {
            public function render(DiagnosticResult $result, OutputInterface $output): int
            {
                return $this->result($result, $output);
            }
        };
        $output = new BufferedOutput();
        try {
            $command->render(new DiagnosticResult(['unencodable' => NAN], 7), $output);
            self::fail('JSON encoding failure must be visible');
        } catch (JsonException) {
            self::assertSame('', $output->fetch());
        }
    }
}
