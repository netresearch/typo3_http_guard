<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{DnsAnswer, DnsQueryInterface, GuardConfig, PolicyException, StaticThenDnsResolver, SystemClock};
use PHPUnit\Framework\TestCase;

final class PolicyResolverBoundariesTest extends TestCase
{
    public function testEightCnameHopsAllowedNineAndSixtyFiveAddressesRejected(): void
    {
        foreach ([8, 9] as $hops) {
            $query = new class ($hops) implements DnsQueryInterface {
                public function __construct(private int $hops) {}

                public function query(string $name, int $type): DnsAnswer
                {
                    $number = (int) substr($name, 1, strpos($name, '.') - 1);
                    $owner  = rtrim($name, '.');
                    $rows   = [];
                    if ($number < $this->hops && $type === 5) {
                        $rows = [
                            [
                                'host'   => $owner,
                                'type'   => 'CNAME',
                                'ttl'    => 5,
                                'target' => 'n' . ($number + 1) . '.example',
                            ],
                        ];
                    }
                    if ($number === $this->hops && $type === 1) {
                        $rows = [['host' => $owner, 'type' => 'A', 'ttl' => 5, 'ip' => '8.8.8.8']];
                    }

                    return new DnsAnswer($rows, 'fixture', true);
                }
            };
            $resolver = new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new SystemClock());
            if ($hops === 8) {
                self::assertSame(['8.8.8.8'], $resolver->resolve('n0.example')->addresses);
                continue;
            }
            try {
                $resolver->resolve('n0.example');
                self::fail('Nine CNAME hops accepted');
            } catch (PolicyException $e) {
                self::assertSame('resolution_limit', $e->reasonCode());
            }
        }
        $query = new class implements DnsQueryInterface {
            public function query(string $name, int $type): DnsAnswer
            {
                $rows = [];
                if ($type === 1) {
                    for ($n = 1; $n <= 65; ++$n) {
                        $rows[] = ['host' => rtrim($name, '.'), 'type' => 'A', 'ttl' => 5, 'ip' => '8.8.4.' . $n];
                    }
                }

                return new DnsAnswer($rows, 'fixture', true);
            }
        };
        try {
            (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new SystemClock()))->resolve('many.example');
            self::fail('65 addresses accepted');
        } catch (PolicyException $e) {
            self::assertSame('resolution_limit', $e->reasonCode());
        }
    }

    public function testTtlZeroNegativeAndCapacityNeverCreateUnboundedPositiveCache(): void
    {
        $query = new class implements DnsQueryInterface {
            public int $calls  = 0;
            public int $ttl    = 0;
            public bool $empty = false;

            public function query(string $name, int $type): DnsAnswer
            {
                ++$this->calls;

                return new DnsAnswer(
                    $type === 1 && !$this->empty ? [['host' => rtrim($name, '.'), 'type' => 'A', 'ttl' => $this->ttl, 'ip' => '8.8.8.8']] : [],
                    'fixture',
                    true,
                );
            }
        };
        $resolver = new StaticThenDnsResolver(
            GuardConfig::fromArray(['resolver' => ['cacheMaxHosts' => 1]]),
            $query,
            new SystemClock(),
        );
        $resolver->resolve('zero.example');
        $resolver->resolve('zero.example');
        self::assertSame(6, $query->calls);
        $query->empty = true;
        for ($i = 0; $i < 2; ++$i) {
            try {
                $resolver->resolve('empty.example');
                self::fail('Empty answer accepted');
            } catch (PolicyException $e) {
                self::assertSame('resolution_unverified', $e->reasonCode());
            }
        }
        self::assertSame(12, $query->calls);
        $query->empty = false;
        $query->ttl   = 120;
        $resolver->resolve('first.example');
        $resolver->resolve('second.example');
        self::assertSame(18, $query->calls);
        $resolver->resolve('second.example');
        self::assertSame(18, $query->calls);
        $resolver->resolve('first.example');
        self::assertSame(21, $query->calls);
    }

    public function testIncompleteAndMalformedRecordsFailBeforeReturningResolution(): void
    {
        foreach ([
            new DnsAnswer([], 'fixture', false),
            new DnsAnswer([['host' => 'x.example', 'type' => 'A', 'ttl' => 1, 'ip' => '::1']], 'fixture', true),
            new DnsAnswer([['host' => 'x.example', 'type' => 'A', 'ttl' => '1', 'ip' => '8.8.8.8']], 'fixture', true),
        ] as $answer) {
            $query = new class ($answer) implements DnsQueryInterface {
                public function __construct(private DnsAnswer $answer) {}

                public function query(string $name, int $type): DnsAnswer
                {
                    return $this->answer;
                }
            };
            try {
                (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, new SystemClock()))->resolve('x.example');
                self::fail('Unverified answer accepted');
            } catch (PolicyException $e) {
                self::assertSame('resolution_unverified', $e->reasonCode());
            }
        }
    }
}
