<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\WireIoBoundaryState as State;
use Netresearch\HttpGuard\WireDnsQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class WireIoBoundaryContractTest extends TestCase
{
    #[DataProvider('nativeFailures')]
    public function testNativeFailureRejectsResolutionAndClosesOnlyCreatedSocket(string $fault, int $closed): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = $fault;
        try {
            (new WireDnsQuery(['127.0.0.1'], 5353, 0.02))->query('wire.example.', 1);
            self::fail('Incomplete native DNS operation produced a verified resolution');
        } catch (PolicyException $exception) {
            self::assertSame('resolution_unverified', $exception->reasonCode());
            self::assertSame('Outbound HTTP policy: resolution_unverified', $exception->getMessage());
        }
        self::assertSame($closed, State::$closed);
        self::assertSame(0, State::$reads, 'Read must not begin after socket, readiness or write failure');
        self::assertSame(
            str_starts_with($fault, 'write-') ? 1 : 0,
            State::$writes,
            'A failed native write must terminate immediately, without retry spinning',
        );
    }

    public static function nativeFailures(): iterable
    {
        foreach (['socket', 'socket-warning'] as $fault) {
            yield $fault => [$fault, 0];
        }
        foreach ([
            'nonblocking',
            'select-false',
            'select-zero',
            'select-two',
            'select-warning',
            'write-false',
            'write-zero',
            'write-partial',
            'write-warning',
        ] as $fault) {
            yield $fault => [$fault, 1];
        }
    }

    #[DataProvider('responseFailures')]
    public function testIncompleteResponseNeverBecomesVerified(string $fault, int $closed): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = $fault;
        try {
            (new WireDnsQuery(['127.0.0.1'], 5353, 0.02))->query('wire.example.', 1);
            self::fail('Incomplete or twice-truncated answer accepted');
        } catch (PolicyException $exception) {
            self::assertSame('resolution_unverified', $exception->reasonCode());
        }
        self::assertSame($closed, State::$closed);
        self::assertGreaterThan(0, State::$reads);
    }

    public static function responseFailures(): iterable
    {
        foreach (['read-false', 'read-empty', 'read-warning'] as $fault) {
            yield $fault => [$fault, 1];
        }
        foreach (['tcp-prefix-short', 'tcp-length-small', 'tcp-body-short', 'tcp-still-truncated'] as $fault) {
            yield $fault => [$fault, 2];
        }
    }

    public function testCompleteUdpAnswerUsesNumericAuthorityAndIsClosed(): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        $answer = (new WireDnsQuery(['::1'], 5353, 0.02))->query('wire.example.', 1);
        self::assertTrue($answer->complete);
        self::assertSame('dns', $answer->source);
        self::assertSame(
            [['host' => 'wire.example', 'type' => 'A', 'ttl' => 5, 'ip' => '203.0.115.7']],
            $answer->records,
        );
        self::assertSame(['udp://[::1]:5353'], State::$authorities);
        self::assertSame(1, State::$closed);
    }

    public function testTcpLengthPrefixAndFragmentedIoProduceCompleteAnswer(): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = 'tcp-fragments';
        $answer       = (new WireDnsQuery(['127.0.0.1'], 5353, 0.02))->query('wire.example.', 1);
        self::assertTrue($answer->complete);
        self::assertSame('203.0.115.7', $answer->records[0]['ip']);
        self::assertSame(['udp://127.0.0.1:5353', 'tcp://127.0.0.1:5353'], State::$authorities);
        self::assertSame(2, State::$closed);
        self::assertGreaterThan(2, State::$writes);
        self::assertGreaterThan(3, State::$reads);
    }

    public function testFailureAtOneNameserverContinuesToNextWithoutNativeFallback(): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = 'first-socket';
        $answer       = (new WireDnsQuery(['127.0.0.1', '::1'], 5353, 0.02))->query('wire.example.', 1);
        self::assertSame('203.0.115.7', $answer->records[0]['ip']);
        self::assertSame(['udp://127.0.0.1:5353', 'udp://[::1]:5353'], State::$authorities);
        self::assertSame(1, State::$closed);
    }

    #[DataProvider('invalidResolverSettings')]
    public function testResolverConfigurationRejectsInvalidLimitsAndNonlistServers(
        ?array $servers,
        int $port,
        float $timeout,
    ): void {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        new WireDnsQuery($servers, $port, $timeout);
    }

    public static function invalidResolverSettings(): iterable
    {
        foreach ([0, 65536] as $port) {
            yield 'port ' . $port => [['127.0.0.1'], $port, 1.0];
        }
        foreach ([0.0, -1.0, 10.001, INF, -INF, NAN] as $n => $timeout) {
            yield 'timeout ' . $n => [['127.0.0.1'], 53, $timeout];
        }
        yield 'nonlist' => [['resolver' => '127.0.0.1'], 53, 1.0];
        yield 'not string' => [[1], 53, 1.0];
    }

    public function testDefaultPortAndNonblockingReadinessRespectRealBoundedTimeBudget(): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        (new WireDnsQuery(['::1'], queryTimeoutSeconds: 2.5))->query('wire.example.', 1);
        self::assertSame(['udp://[::1]:53'], State::$authorities);
        self::assertSame([false], State::$blocking);
        self::assertSame(['ready-write', 'write', 'ready-read', 'read'], State::$events);
        foreach (State::$timeouts as $timeout) {
            self::assertGreaterThan(0, $timeout);
            self::assertLessThanOrEqual(2.5, $timeout);
        }
        foreach (State::$readiness as [$seconds, $micros]) {
            self::assertGreaterThanOrEqual(0, $seconds);
            self::assertLessThanOrEqual(2, $seconds);
            self::assertGreaterThanOrEqual(0, $micros);
            self::assertLessThan(1000000, $micros);
        }
    }

    public function testThreeNameserversAndBoundaryPortsRemainValid(): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = 'socket';
        foreach ([1, 65535] as $port) {
            try {
                (new WireDnsQuery(['127.0.0.1', '::1', '203.0.115.7'], $port, 10))->query('wire.example.', 1);
                self::fail('Socket false resolved');
            } catch (PolicyException $exception) {
                self::assertSame('resolution_unverified', $exception->reasonCode());
            }
        }
        self::assertCount(6, State::$authorities);
    }

    public function testResolutionLimitCannotBeRetriedAsAnOrdinaryNameserverFailure(): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = 'record-limit';
        try {
            (new WireDnsQuery(['127.0.0.1', '::1'], 5353))->query('wire.example.', 1);
            self::fail('4097 records accepted');
        } catch (PolicyException $exception) {
            self::assertSame('resolution_limit', $exception->reasonCode());
        }
        self::assertSame(1, State::$opened);
        self::assertSame(1, State::$closed);
    }

    public function testSystemResolverParsesOnlyNumericServersAndCachesValidatedConfiguration(): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        State::$fault = 'socket';
        $path         = tempnam(sys_get_temp_dir(), 'wire-contract-');
        self::assertIsString($path);
        try {
            file_put_contents(
                $path,
                "# Synthetic resolver control\n\n nameserver 127.0.0.1 # local\nnameserver ::1; local\nnameserver 127.0.0.1\nsearch ignored.example\ndomain ignored.example\noptions rotate\nsortlist 127.0.0.0/8\n",
            );
            $query = new WireDnsQuery(resolvConfPath: $path);
            for ($n = 0; $n < 2; ++$n) {
                try {
                    $query->query('wire.example.', 1);
                    self::fail('Socket false resolved');
                } catch (PolicyException $exception) {
                    self::assertSame('resolution_unverified', $exception->reasonCode());
                }
                file_put_contents($path, 'unrecognized directive');
            }
            self::assertSame(
                ['udp://127.0.0.1:53', 'udp://[::1]:53', 'udp://127.0.0.1:53', 'udp://[::1]:53'],
                State::$authorities,
            );
        } finally {
            // Removes only this test's freshly created synthetic resolver file; no caller path.
            // nosemgrep: php.lang.security.unlink-use.unlink-use
            unlink($path);
        }
    }

    #[DataProvider('invalidResolverFiles')]
    public function testSystemResolverRejectsMalformedOrUnboundedFilesBeforeSocket(string $text): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        $path = tempnam(sys_get_temp_dir(), 'wire-contract-');
        self::assertIsString($path);
        try {
            file_put_contents($path, $text);
            try {
                (new WireDnsQuery(resolvConfPath: $path))->query('wire.example.', 1);
                self::fail('Malformed system DNS accepted');
            } catch (PolicyException $exception) {
                self::assertSame('configuration_invalid', $exception->reasonCode());
            }
            self::assertSame(0, State::$opened);
        } finally {
            // Removes only this test's freshly created synthetic resolver file; no caller path.
            // nosemgrep: php.lang.security.unlink-use.unlink-use
            unlink($path);
        }
    }

    public static function invalidResolverFiles(): iterable
    {
        foreach ([
            '',
            '# no nameserver',
            'nameserver',
            'nameserver 127.0.0.1 extra',
            'nameserver resolver.example',
            'nameserver 127.0.0.1%scope',
            'unexpected directive',
            "nameserver 127.0.0.1\nnameserver ::1\nnameserver 203.0.115.7\nnameserver 203.0.115.8",
        ] as $n => $text) {
            yield 'resolver text ' . $n => [$text];
        }
        yield '65537 bytes' => ["nameserver 127.0.0.1\n#" . str_repeat('x', 65537 - strlen("nameserver 127.0.0.1\n#"))];
    }

    public function testExactly65536ByteResolverFileRemainsBoundedAndUsable(): void
    {
        require __DIR__ . '/Fixtures/WireIoBoundaryShim.php';
        $path = tempnam(sys_get_temp_dir(), 'wire-contract-');
        self::assertIsString($path);
        try {
            file_put_contents(
                $path,
                "nameserver 127.0.0.1\n#" . str_repeat('x', 65536 - strlen("nameserver 127.0.0.1\n#")),
            );
            self::assertTrue((new WireDnsQuery(resolvConfPath: $path))->query('wire.example.', 1)->complete);
            self::assertSame(['udp://127.0.0.1:53'], State::$authorities);
        } finally {
            // Removes only this test's freshly created synthetic resolver file; no caller path.
            // nosemgrep: php.lang.security.unlink-use.unlink-use
            unlink($path);
        }
    }
}
