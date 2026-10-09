<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use DateTimeImmutable;
use Netresearch\HttpGuard\ClockInterface;
use Netresearch\HttpGuard\DnsAnswer;
use Netresearch\HttpGuard\DnsQueryInterface;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\StaticThenDnsResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResidualResolverClock implements ClockInterface
{
    public float $seconds = 0;

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-10T00:00:00Z');
    }

    public function monotonic(): float
    {
        return $this->seconds;
    }
}
final class ResidualResolverQuery implements DnsQueryInterface
{
    public array $answers = [];
    public array $calls   = [];

    public function query(string $name, int $type): DnsAnswer
    {
        $this->calls[] = [$name, $type];

        return $this->answers[$name][$type] ?? new DnsAnswer([], 'empty', true);
    }
}
final class ResolverResidualCacheContractTest extends TestCase
{
    public function testZeroLifetimeAnswerNeverDisplacesAnotherLiveCachedHost(): void
    {
        $query          = new ResidualResolverQuery();
        $query->answers = [
            'live.example.' => [
                1 => new DnsAnswer([['host' => 'live.example', 'type' => 'A', 'ttl' => 5, 'ip' => '8.8.8.8']], 'dns', true),
            ],
            'zero.example.' => [
                1 => new DnsAnswer([['host' => 'zero.example', 'type' => 'A', 'ttl' => 0, 'ip' => '1.1.1.1']], 'dns', true),
            ],
        ];
        $resolver = new StaticThenDnsResolver(
            GuardConfig::fromArray(['resolver' => ['cacheMaxHosts' => 1]]),
            $query,
            new ResidualResolverClock(),
        );
        $live = $resolver->resolve('live.example');
        self::assertSame(0, $resolver->resolve('zero.example')->ttlSeconds);
        self::assertSame($live, $resolver->resolve('live.example'));
        self::assertCount(6, $query->calls);
        $resolver->resolve('zero.example');
        self::assertCount(9, $query->calls);
        self::assertSame($live, $resolver->resolve('live.example'));
        self::assertCount(9, $query->calls);
    }

    public function testDuplicateAliasTargetsCannotExtendTheFirstAnswerLifetime(): void
    {
        $query          = new ResidualResolverQuery();
        $clock          = new ResidualResolverClock();
        $query->answers = [
            'root.example.' => [
                1 => new DnsAnswer(
                    [['host' => 'root.example', 'type' => 'CNAME', 'ttl' => 2, 'target' => '127.0.0.1.example']],
                    'first',
                    true,
                ),
                28 => new DnsAnswer(
                    [['host' => 'root.example', 'type' => 'CNAME', 'ttl' => 9, 'target' => '127.0.0.1.example']],
                    'second',
                    true,
                ),
                5 => new DnsAnswer(
                    [['host' => 'root.example', 'type' => 'CNAME', 'ttl' => 7, 'target' => '127.0.0.1.example']],
                    'third',
                    true,
                ),
            ],
            '127.0.0.1.example.' => [
                1 => new DnsAnswer(
                    [['host' => '127.0.0.1.example', 'type' => 'A', 'ttl' => 60, 'ip' => '8.8.8.8']],
                    'address',
                    true,
                ),
            ],
        ];
        $resolver = new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, $clock);
        $first    = $resolver->resolve('root.example');
        self::assertSame(['127.0.0.1.example'], $first->cnameChain);
        self::assertSame(['8.8.8.8'], $first->addresses);
        self::assertSame(2, $first->ttlSeconds);
        self::assertSame('first+second+third+address+empty', $first->source);
        self::assertCount(6, $query->calls);
        $clock->seconds = 1.999;
        self::assertSame($first, $resolver->resolve('root.example'));
        $clock->seconds = 2;
        self::assertNotSame($first, $resolver->resolve('root.example'));
        self::assertCount(12, $query->calls);
    }

    public function testAliasCycleStopsBeforeQueryingASeenNameAgain(): void
    {
        $query = new ResidualResolverQuery();
        foreach (['first.example' => 'second.example', 'second.example' => 'first.example'] as $owner => $target) {
            $query->answers[$owner . '.'][1] = new DnsAnswer([['host' => $owner, 'type' => 'CNAME', 'ttl' => 5, 'target' => $target]], 'dns', true);
        }
        $resolver = new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new ResidualResolverClock());
        try {
            $resolver->resolve('first.example');
            self::fail('Alias cycle accepted');
        } catch (PolicyException $error) {
            self::assertSame('resolution_limit', $error->reasonCode());
        }
        self::assertSame(
            [
                ['first.example.', 1],
                ['first.example.', 28],
                ['first.example.', 5],
                ['second.example.', 1],
                ['second.example.', 28],
                ['second.example.', 5],
            ],
            $query->calls,
        );
    }

    #[DataProvider('invalidFamilyLiterals')]
    public function testRecordLiteralsFailWithControlledReasonBeforeReturningAddresses(
        string $kind,
        string $field,
        string $value,
    ): void {
        $query          = new ResidualResolverQuery();
        $query->answers = [
            'api.example.' => [
                1 => new DnsAnswer([['host' => 'api.example', 'type' => $kind, 'ttl' => 5, $field => $value]], 'dns', true),
            ],
        ];
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new ResidualResolverClock()))->resolve(
            'api.example',
        );
    }

    public static function invalidFamilyLiterals(): iterable
    {
        yield 'AAAA carries IPv4' => ['AAAA', 'ipv6', '8.8.8.8'];
        yield 'A carries IPv6' => ['A', 'ip', '2606:4700:4700::1111'];
        yield 'A address has NUL suffix' => ['A', 'ip', "8.8.8.8\x00"];
        yield 'AAAA address has NUL prefix' => ['AAAA', 'ipv6', "\x002606:4700:4700::1111"];
    }

    #[DataProvider('acceptedFamilyLiterals')]
    public function testOrdinaryAddressFamiliesStillProduceCanonicalCompleteResolution(
        string $kind,
        string $field,
        string $value,
        string $canonical,
    ): void {
        $query          = new ResidualResolverQuery();
        $query->answers = [
            'api.example.' => [
                1 => new DnsAnswer([['host' => 'api.example', 'type' => $kind, 'ttl' => 5, $field => $value]], 'dns', true),
            ],
        ];
        $resolution = (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new ResidualResolverClock()))->resolve(
            'api.example',
        );
        self::assertSame([$canonical], $resolution->addresses);
        self::assertSame(5, $resolution->ttlSeconds);
        self::assertSame([], $resolution->cnameChain);
        self::assertSame([['api.example.', 1], ['api.example.', 28], ['api.example.', 5]], $query->calls);
    }

    public static function acceptedFamilyLiterals(): iterable
    {
        yield 'ordinary A record' => ['A', 'ip', '8.8.8.8', '8.8.8.8'];
        yield 'ordinary expanded AAAA record' => ['AAAA', 'ipv6', '2606:4700:4700:0:0:0:0:1111', '2606:4700:4700::1111'];
    }
}
