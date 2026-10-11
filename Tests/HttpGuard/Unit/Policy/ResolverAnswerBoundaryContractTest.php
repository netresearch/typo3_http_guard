<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use DateTimeImmutable;
use Netresearch\HttpGuard\{ClockInterface, DnsAnswer, DnsQueryInterface, GuardConfig, PolicyException, StaticThenDnsResolver};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ResolverBoundaryClock implements ClockInterface
{
    public float $elapsed = 0;

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-09T00:00:00Z');
    }

    public function monotonic(): float
    {
        return $this->elapsed;
    }
}
final class ResolverBoundaryQuery implements DnsQueryInterface
{
    public array $responses              = [];
    public array $calls                  = [];
    public ?ResolverBoundaryClock $clock = null;
    public float $cost                   = 0;
    public bool $throw                   = false;

    public function query(string $absoluteFqdn, int $qtype): DnsAnswer
    {
        $this->calls[] = [$absoluteFqdn, $qtype];
        if ($this->clock instanceof ResolverBoundaryClock) {
            $this->clock->elapsed += $this->cost;
        }
        if ($this->throw) {
            throw new RuntimeException('Synthetic DNS implementation failure');
        }

        return $this->responses[$absoluteFqdn][$qtype] ?? new DnsAnswer([], 'source' . $qtype, true);
    }
}
final class ResolverAnswerBoundaryContractTest extends TestCase
{
    public function testStaticBindingBypassesDnsAndSnapshotTracksConfigAndCanonicalHost(): void
    {
        $query  = new ResolverBoundaryQuery();
        $config = GuardConfig::fromArray(
            ['resolver' => ['staticHosts' => ['1api.example' => ['203.0.115.7'], 'api.example1' => ['203.0.115.7']]]],
        );
        $resolver = new StaticThenDnsResolver($config, $query, new ResolverBoundaryClock());
        $first    = $resolver->resolve('1API.Example.');
        self::assertSame(['203.0.115.7'], $first->addresses);
        self::assertSame('static', $first->source);
        self::assertNull($first->ttlSeconds);
        self::assertSame([], $first->cnameChain);
        self::assertSame($first->generation, $resolver->resolve('1api.example')->generation);
        self::assertNotSame($first->generation, $resolver->resolve('api.example1')->generation);
        $changed = GuardConfig::fromArray(
            ['redirects' => ['max' => 0], 'resolver' => ['staticHosts' => ['1api.example' => ['203.0.115.7']]]],
        );
        self::assertNotSame(
            $first->generation,
            (new StaticThenDnsResolver($changed, $query, new ResolverBoundaryClock()))->resolve('1api.example')->generation,
        );
        self::assertSame([], $query->calls);
    }

    public function testAAndAaaaQuestionsAreAllQueriedAndDuplicateTtlNeverExtendsCache(): void
    {
        $clock            = new ResolverBoundaryClock();
        $query            = new ResolverBoundaryQuery();
        $query->responses = [
            'api.example.' => [
                1  => new DnsAnswer([['host' => 'api.example', 'type' => 'A', 'ttl' => 2, 'ip' => '203.0.115.7']], 'v4', true),
                28 => new DnsAnswer(
                    [
                        ['host' => 'api.example', 'type' => 'AAAA', 'ttl' => 6, 'ipv6' => '2001:4860::8888'],
                        ['host' => 'api.example', 'type' => 'A', 'ttl' => 9, 'ip' => '203.0.115.7'],
                    ],
                    'v6',
                    true,
                ),
                5 => new DnsAnswer([], 'alias', true),
            ],
        ];
        $resolver = new StaticThenDnsResolver(GuardConfig::fromArray(['resolver' => ['maxAddresses' => 2]]), $query, $clock);
        $first    = $resolver->resolve('api.example');
        self::assertSame([['api.example.', 1], ['api.example.', 28], ['api.example.', 5]], $query->calls);
        self::assertSame(['203.0.115.7', '2001:4860::8888'], $first->addresses);
        self::assertSame('v4+v6+alias', $first->source);
        self::assertSame(2, $first->ttlSeconds);
        $clock->elapsed = 1.999;
        self::assertSame($first, $resolver->resolve('api.example'));
        $clock->elapsed = 2;
        self::assertNotSame($first, $resolver->resolve('api.example'));
        self::assertCount(6, $query->calls);
    }

    public function testCnameMinimumIncludesEarlierLinksAndElapsedQueryTime(): void
    {
        $clock            = new ResolverBoundaryClock();
        $query            = new ResolverBoundaryQuery();
        $query->clock     = $clock;
        $query->cost      = 0.25;
        $query->responses = [
            '1api.example.' => [
                1 => new DnsAnswer(
                    [['host' => '1api.example', 'type' => 'CNAME', 'ttl' => 4, 'target' => 'alias.example1']],
                    'dns',
                    true,
                ),
            ],
            'alias.example1.' => [
                1 => new DnsAnswer(
                    [['host' => 'alias.example1', 'type' => 'CNAME', 'ttl' => 8, 'target' => 'final.example']],
                    'dns',
                    true,
                ),
            ],
            'final.example.' => [
                1 => new DnsAnswer(
                    [['host' => 'final.example', 'type' => 'A', 'ttl' => 60, 'ip' => '203.0.115.7']],
                    'dns',
                    true,
                ),
            ],
        ];
        $resolver = new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, $clock);
        $answer   = $resolver->resolve('1api.example');
        self::assertSame(['alias.example1', 'final.example'], $answer->cnameChain);
        self::assertSame(1, $answer->ttlSeconds, 'Fractional remaining lifetime must be rounded down');
        self::assertCount(9, $query->calls);
        $clock->elapsed = 3.25;
        self::assertNotSame($answer, $resolver->resolve('1api.example'));
        self::assertCount(18, $query->calls);
    }

    #[DataProvider('malformedAnswers')]
    public function testMalformedRecordAndIncompleteSetNeverReturnsResolution(DnsAnswer $answer): void
    {
        $query            = new ResolverBoundaryQuery();
        $query->responses = ['api.example.' => [1 => $answer]];
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new ResolverBoundaryClock()))->resolve(
            'api.example',
        );
    }

    public static function malformedAnswers(): iterable
    {
        yield 'associative records' => [
            new DnsAnswer(
                ['bad' => ['host' => 'api.example', 'type' => 'A', 'ttl' => 1, 'ip' => '203.0.115.7']],
                'dns',
                true,
            ),
        ];
        yield 'incomplete populated answer' => [new DnsAnswer([['host' => 'api.example', 'type' => 'A', 'ttl' => 1, 'ip' => '203.0.115.7']], 'dns', false)];
        foreach ([
            false,
            ['type' => 'A', 'ttl' => 1, 'ip' => '203.0.115.7'],
            ['host' => 1, 'type' => 'A', 'ttl' => 1],
            ['host' => 'api.example', 'type' => false, 'ttl' => 1],
            ['host' => 'api.example', 'type' => 'TXT', 'ttl' => 1],
            ['host' => 'api.example', 'type' => 'A', 'ttl' => -1, 'ip' => '203.0.115.7'],
            ['host' => 'bad_host', 'type' => 'A', 'ttl' => 1, 'ip' => '203.0.115.7'],
            ['host' => 'api.example', 'type' => 'A', 'ttl' => 1],
            ['host' => 'api.example', 'type' => 'A', 'ttl' => 1, 'ip' => false],
            ['host' => 'api.example', 'type' => 'A', 'ttl' => 1, 'ip' => 'bad'],
            ['host' => 'api.example', 'type' => 'AAAA', 'ttl' => 1, 'ipv6' => '203.0.115.7'],
            ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1],
            ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => false],
            ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => 'bad_host'],
            ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => '127.0.0.1'],
            ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => '::1'],
        ] as $n => $row) {
            yield 'record ' . $n => [new DnsAnswer([$row], 'dns', true)];
        }
        yield 'conflicting aliases' => [
            new DnsAnswer(
                [
                    ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => 'first.example'],
                    ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => 'second.example'],
                ],
                'dns',
                true,
            ),
        ];
        yield 'alias plus address' => [
            new DnsAnswer(
                [
                    ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => 'alias.example'],
                    ['host' => 'api.example', 'type' => 'A', 'ttl' => 1, 'ip' => '203.0.115.7'],
                ],
                'dns',
                true,
            ),
        ];
    }

    #[DataProvider('unusableHosts')]
    public function testNumericAndMalformedDnsHostNeverCallsQuery(string $host): void
    {
        $query = new ResolverBoundaryQuery();
        try {
            (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new ResolverBoundaryClock()))->resolve($host);
            self::fail('Non-DNS host accepted');
        } catch (PolicyException $exception) {
            self::assertSame('resolution_unverified', $exception->reasonCode());
        }
        self::assertSame([], $query->calls);
    }

    public static function unusableHosts(): iterable
    {
        foreach (['127.0.0.1', '::1', '[::1]', 'bad_host', '', 'host' . chr(10)] as $host) {
            yield [$host];
        }
    }

    public function testUnderlyingExceptionHasFixedPolicyReasonAndNoNegativeCache(): void
    {
        $query        = new ResolverBoundaryQuery();
        $query->throw = true;
        $resolver     = new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new ResolverBoundaryClock());
        for ($n = 0; $n < 2; ++$n) {
            try {
                $resolver->resolve('api.example');
                self::fail('Exception resolved');
            } catch (PolicyException $exception) {
                self::assertSame('resolution_unverified', $exception->reasonCode());
            }
        }
        self::assertCount(2, $query->calls);
    }
}
