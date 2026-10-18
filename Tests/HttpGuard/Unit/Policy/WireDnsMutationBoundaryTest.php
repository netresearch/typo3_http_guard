<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{PolicyException, WireDnsQuery};
use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\WireIoBoundaryState as State;
use PHPUnit\Framework\Attributes\{DataProvider, PreserveGlobalState, RunTestsInSeparateProcesses};
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class WireDnsMutationBoundaryTest extends TestCase
{
    #[DataProvider('idBoundaries')]
    public function testExtremeGeneratedTransactionIdsStillResolve(string $boundary): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$idBoundary = $boundary;
        self::assertTrue((new WireDnsQuery(['127.0.0.1'], 5353))->query('wire.example.', 1)->complete);
    }

    public static function idBoundaries(): iterable
    {
        yield 'lowest generated id' => ['min'];
        yield 'highest generated id' => ['max'];
    }

    #[DataProvider('tcpWriteFailures')]
    public function testTcpWriteFailureStopsWithoutRetryOrRead(string $fault): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = $fault;
        $this->assertResolutionFailure(new WireDnsQuery(['127.0.0.1'], 5353));
        self::assertSame(1, State::$tcpWrites);
        self::assertSame(1, State::$reads, 'Only the initial truncated UDP reply may be read');
        self::assertSame(2, State::$closed);
    }

    public static function tcpWriteFailures(): iterable
    {
        yield 'false' => ['tcp-write-false'];
        yield 'zero' => ['tcp-write-zero'];
    }

    #[DataProvider('tcpReadFailures')]
    public function testTcpBodyReadFailureStopsAtItsFirstIncompleteRead(string $fault): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = $fault;
        $this->assertResolutionFailure(new WireDnsQuery(['127.0.0.1'], 5353));
        self::assertSame(1, State::$tcpBodyReads);
        self::assertSame(2, State::$closed);
    }

    public static function tcpReadFailures(): iterable
    {
        yield 'false' => ['tcp-read-false'];
        yield 'empty' => ['tcp-read-empty'];
    }

    public function testImpossibleTcpLengthIsRejectedBeforeReadingItsBody(): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = 'tcp-length-small';
        $this->assertResolutionFailure(new WireDnsQuery(['127.0.0.1'], 5353));
        self::assertSame(2, State::$reads, 'Read the UDP reply and TCP prefix, without reading an invalid body');
        self::assertSame(2, State::$closed);
    }

    public function testExpiredExchangeBudgetOpensNoSocket(): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$times = [1000000000, 2000000000, 2000000000];
        $this->assertResolutionFailure(new WireDnsQuery(['127.0.0.1'], 5353, 1.0));
        self::assertSame(0, State::$opened);
    }

    public function testExpiredReadinessBudgetWritesNoQuery(): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$times = [1000000000, 1000000000, 2000000000, 2000000000];
        $this->assertResolutionFailure(new WireDnsQuery(['127.0.0.1'], 5353, 1.0));
        self::assertSame(0, State::$writes);
        self::assertSame(1, State::$closed);
    }

    public function testUnknownDirectiveCannotHideBehindAValidNameserver(): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        $path = $this->resolverFile("nameserver 127.0.0.1\nunrecognized directive\n");
        try {
            try {
                (new WireDnsQuery(resolvConfPath: $path))->query('wire.example.', 1);
                self::fail('Unknown resolver directive was accepted');
            } catch (PolicyException $error) {
                self::assertSame('configuration_invalid', $error->reasonCode());
            }
            self::assertSame(0, State::$opened);
        } finally {
            // Removes only this test's freshly created synthetic fixture; no caller path.
            // nosemgrep: php.lang.security.unlink-use.unlink-use
            unlink($path);
        }
    }

    #[DataProvider('duplicateResolverFiles')]
    public function testDuplicateSystemDeclarationsStillProduceAUsableNumericServerList(string $text): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        $path = $this->resolverFile($text);
        try {
            self::assertTrue((new WireDnsQuery(resolvConfPath: $path))->query('wire.example.', 1)->complete);
            self::assertSame(['udp://127.0.0.1:53'], State::$authorities);
        } finally {
            // Removes only this test's freshly created synthetic fixture; no caller path.
            // nosemgrep: php.lang.security.unlink-use.unlink-use
            unlink($path);
        }
    }

    public static function duplicateResolverFiles(): iterable
    {
        yield 'four repetitions are one server' => [str_repeat("nameserver 127.0.0.1\n", 4)];
        yield 'interleaved duplicate leaves a second unique server' => ["nameserver 127.0.0.1\nnameserver 127.0.0.1\nnameserver ::1\n"];
    }

    public function testSystemFileRetriesEveryUniqueNameserverAfterInterleavedDuplicates(): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = 'socket';
        $path         = $this->resolverFile("nameserver 127.0.0.1\nnameserver 127.0.0.1\nnameserver ::1\n");
        try {
            $this->assertResolutionFailure(new WireDnsQuery(resolvConfPath: $path));
            self::assertSame(['udp://127.0.0.1:53', 'udp://[::1]:53'], State::$authorities);
        } finally {
            // Removes only this test's freshly created synthetic fixture; no caller path.
            // nosemgrep: php.lang.security.unlink-use.unlink-use
            unlink($path);
        }
    }

    public function testExplicitDuplicateServersDoNotRepeatAnAttempt(): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = 'socket';
        $this->assertResolutionFailure(new WireDnsQuery(['127.0.0.1', '127.0.0.1', '::1'], 5353));
        self::assertSame(['udp://127.0.0.1:5353', 'udp://[::1]:5353'], State::$authorities);
    }

    public function testRelativeResolverPathIsNotRedirectedThroughPhpIncludePath(): void
    {
        require_once __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        $directory = $this->resolverFile('');
        // Removes only this test's freshly created synthetic fixture; no caller path.
        // nosemgrep: php.lang.security.unlink-use.unlink-use
        unlink($directory);
        mkdir($directory);
        mkdir($directory . '/shadow');
        file_put_contents($directory . '/resolv.conf', "nameserver 127.0.0.1\n");
        file_put_contents($directory . '/shadow/resolv.conf', "nameserver ::1\n");
        $cwd         = getcwd();
        $includePath = get_include_path();
        try {
            self::assertTrue(chdir($directory));
            set_include_path($directory . '/shadow');
            self::assertTrue((new WireDnsQuery(resolvConfPath: 'resolv.conf'))->query('wire.example.', 1)->complete);
            self::assertSame(['udp://127.0.0.1:53'], State::$authorities);
        } finally {
            set_include_path($includePath);
            chdir($cwd);
            // Removes only this test's freshly created synthetic fixture; no caller path.
            // nosemgrep: php.lang.security.unlink-use.unlink-use
            unlink($directory . '/resolv.conf');
            // Removes only this test's freshly created synthetic fixture; no caller path.
            // nosemgrep: php.lang.security.unlink-use.unlink-use
            unlink($directory . '/shadow/resolv.conf');
            rmdir($directory . '/shadow');
            rmdir($directory);
        }
    }

    private function assertResolutionFailure(WireDnsQuery $query): void
    {
        try {
            $query->query('wire.example.', 1);
            self::fail('Incomplete DNS operation produced a resolution');
        } catch (PolicyException $error) {
            self::assertSame('resolution_unverified', $error->reasonCode());
        }
    }

    private function resolverFile(string $text): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dns-mutation-');
        self::assertIsString($path);
        file_put_contents($path, $text);

        return $path;
    }
}
