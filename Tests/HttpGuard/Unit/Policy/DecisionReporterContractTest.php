<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use DateTimeImmutable;
use Netresearch\HttpGuard\ClockInterface;
use Netresearch\HttpGuard\DecisionEvent;
use Netresearch\HttpGuard\DecisionReporter;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\ReporterFunctionProbe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ReporterContractClock implements ClockInterface
{
    public float $seconds = 100;

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-09T12:00:00Z');
    }

    public function monotonic(): float
    {
        return $this->seconds;
    }
}
final class DecisionReporterContractTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[DataProvider('samplingCases')]
    public function testAllowedSamplingKeepsBoundaryAndCountsEveryDecision(
        float $rate,
        array $draws,
        int $logged,
    ): void {
        require_once __DIR__ . '/Fixtures/ReporterFunctions.php';
        ReporterFunctionProbe::$draws = $draws;
        $rows                         = [];
        $reporter                     = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['allowedSampleRate' => $rate]]),
            new ReporterContractClock(),
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
        );
        $reporter->report($this->event('allow'));
        self::assertCount($logged, $rows);
        self::assertSame([], ReporterFunctionProbe::$draws);
        self::assertCount(count($draws), ReporterFunctionProbe::$ranges);
        self::assertSame(
            [
                [
                    'labels' => ['mode' => 'enforce', 'decision' => 'allow', 'reasonCode' => null, 'profileId' => null],
                    'count'  => 1,
                ],
            ],
            $reporter->metrics(),
        );
        self::assertSame(0, $reporter->loggerFailureCount());
    }

    /** @return iterable<string,array{float,list<int>,int}> */
    public static function samplingCases(): iterable
    {
        yield 'disabled sample has no entropy' => [0, [], 0];
        yield 'full sample has no entropy' => [1, [], 1];
        yield 'lower draw endpoint' => [0.5, [0], 1];
        yield 'half exactly admitted' => [0.5, [500000], 1];
        yield 'half just above rejected' => [0.5, [500001], 0];
        yield 'upper draw endpoint' => [0.5, [1000000], 0];
        yield 'quarter exactly admitted' => [0.25, [250000], 1];
        yield 'quarter just above rejected' => [0.25, [250001], 0];
        yield 'fractional rate below half' => [0.4999998, [500000], 0];
        yield 'fractional rate below one' => [0.9999998, [1000000], 0];
        yield 'largest draw below fractional rate' => [0.9999998, [999999], 1];
    }

    public function testOneSharedNonAllowBudgetDoesNotSuppressAllowedLogs(): void
    {
        $rows     = [];
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['denyRateLimitPerMinute' => 1, 'allowedSampleRate' => 1]]),
            new ReporterContractClock(),
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
        );
        foreach (['deny', 'would_deny', 'unverifiable', 'allow'] as $decision) {
            $reporter->report($this->event($decision));
        }
        self::assertSame(['deny', 'allow'], array_column($rows, 'decision'));
        self::assertSame(4, array_sum(array_column($reporter->metrics(), 'count')));
        self::assertSame(0, $reporter->loggerFailureCount());
    }

    public function testFailedLoggerConsumesBudgetUntilTheNextWindowWithoutLosingMetrics(): void
    {
        $clock    = new ReporterContractClock();
        $calls    = 0;
        $rows     = [];
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['denyRateLimitPerMinute' => 2]]),
            $clock,
            static function (array $row) use (&$calls, &$rows): void {
                ++$calls;
                if ($calls <= 2) {
                    throw new RuntimeException('synthetic private log provider detail');
                }
                $rows[] = $row;
            },
        );
        for ($i = 0; $i < 3; ++$i) {
            $reporter->report($this->event('deny'));
        }
        self::assertSame(2, $calls);
        self::assertSame(2, $reporter->loggerFailureCount());
        self::assertSame([], $rows);
        $clock->seconds = 159.999;
        $reporter->report($this->event('deny'));
        self::assertSame(2, $calls);
        $clock->seconds = 160;
        $reporter->report($this->event('deny'));
        self::assertSame(3, $calls);
        self::assertCount(1, $rows);
        self::assertSame(2, $reporter->loggerFailureCount());
        self::assertSame(
            [
                [
                    'labels' => [
                        'mode'       => 'enforce',
                        'decision'   => 'deny',
                        'reasonCode' => 'address_forbidden',
                        'profileId'  => null,
                    ],
                    'count' => 5,
                ],
            ],
            $reporter->metrics(),
        );
        self::assertSame(
            ['metrics' => $reporter->metrics(), 'loggerFailures' => 2, 'hostHmacKeyConfigured' => false],
            $reporter->__debugInfo(),
        );
        self::assertStringNotContainsString(
            'synthetic private',
            json_encode($reporter->__debugInfo(), JSON_THROW_ON_ERROR),
        );
    }

    public function testAbsentLoggerKeepsCountersWithoutAnInvocationFailure(): void
    {
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['allowedSampleRate' => 1]]),
            new ReporterContractClock(),
        );
        $reporter->report($this->event('allow'));
        $reporter->report($this->event('deny'));
        self::assertSame(2, array_sum(array_column($reporter->metrics(), 'count')));
        self::assertSame(
            ['metrics' => $reporter->metrics(), 'loggerFailures' => 0, 'hostHmacKeyConfigured' => false],
            $reporter->__debugInfo(),
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOptionalNativeWipeIsInvokedOnceAndOnlyReporterOwnedRetentionEnds(): void
    {
        if (!function_exists('sodium_memzero')) {
            self::markTestSkipped('Optional Sodium wipe is unavailable on this real runtime');
        }
        require_once __DIR__ . '/Fixtures/ReporterFunctions.php';
        $key      = 'synthetic bounded-lifetime key';
        $rows     = [];
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['hostHmacKeyEnv' => 'HTTP_GUARD_TEST_KEY']]),
            new ReporterContractClock(),
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
            $key,
        );
        $reporter->report($this->event('deny'));
        self::assertSame(hash_hmac('sha256', 'api.example', $key), $rows[0]['host']);
        self::assertSame(
            ['metrics' => $reporter->metrics(), 'loggerFailures' => 0, 'hostHmacKeyConfigured' => true],
            $reporter->__debugInfo(),
        );
        $reporter->clearHostHmacKey();
        $reporter->clearHostHmacKey();
        self::assertSame(1, ReporterFunctionProbe::$wipes);
        self::assertSame('synthetic bounded-lifetime key', $key);
        $reporter->report($this->event('deny'));
        self::assertArrayNotHasKey('host', $rows[1]);
        self::assertSame(
            ['metrics' => $reporter->metrics(), 'loggerFailures' => 0, 'hostHmacKeyConfigured' => false],
            $reporter->__debugInfo(),
        );
        $reporter->__destruct();
        self::assertSame(1, ReporterFunctionProbe::$wipes);
    }

    private function event(string $decision): DecisionEvent
    {
        return new DecisionEvent(
            1,
            'ignored',
            'enforce',
            $decision,
            $decision === 'allow' ? null : 'address_forbidden',
            null,
            'ignored',
            'private_ipv4',
            'https',
            443,
            'static',
            str_repeat('a', 24),
            'api.example',
        );
    }

    #[DataProvider('hostPolicies')]
    public function testHostLoggingHonorsExplicitPolicyWithoutExposingRetainedSecrets(
        string $mode,
        ?string $key,
        string $host,
        ?string $expectedHost,
        bool $keyConfigured,
    ): void {
        $rows     = [];
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['hostMode' => $mode, 'hostHmacKeyEnv' => 'HTTP_GUARD_TEST_KEY']]),
            new ReporterContractClock(),
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
            $key,
        );
        $reporter->report(
            new DecisionEvent(
                1,
                'ignored',
                'enforce',
                'deny',
                'address_forbidden',
                null,
                'ignored',
                'private_ipv4',
                'https',
                443,
                'static',
                str_repeat('a', 24),
                $host,
            ),
        );
        self::assertCount(1, $rows);
        if ($expectedHost === null) {
            self::assertArrayNotHasKey('host', $rows[0]);
        } else {
            self::assertSame($expectedHost, $rows[0]['host']);
        }
        self::assertSame(
            ['metrics' => $reporter->metrics(), 'loggerFailures' => 0, 'hostHmacKeyConfigured' => $keyConfigured],
            $reporter->__debugInfo(),
        );
        foreach (['headers', 'query', 'path', 'request', 'hostHmacKey'] as $field) {
            self::assertArrayNotHasKey($field, $rows[0]);
        }
    }

    /** @return iterable<string,array{string,?string,string,?string,bool}> */
    public static function hostPolicies(): iterable
    {
        yield 'missing hash key omits host' => ['hash', null, 'API.EXAMPLE.', null, false];
        yield 'empty hash key omits host' => ['hash', '', 'API.EXAMPLE.', null, false];
        yield 'zero string is a real key' => ['hash', '0', 'API.EXAMPLE.', hash_hmac('sha256', 'api.example', '0'), true];
        yield 'operator key is not trimmed' => ['hash', ' operator key ', 'API.EXAMPLE.', hash_hmac('sha256', 'api.example', ' operator key '), true];
        yield 'plain opt-in canonicalizes and releases unused key' => ['plain', 'unused private key', 'API.EXAMPLE.', 'api.example', false];
        yield 'plain does not expose invalid host text' => ['plain', null, 'api.example/private-query', null, false];
    }
}
