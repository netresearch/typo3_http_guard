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

final class ResolverMutationBoundaryTest extends TestCase
{
    #[DataProvider('invalidAliasAnswers')]
    public function testUnverifiableAliasStopsBeforeAnyFurtherQuestion(array $records): void
    {
        $query = new class ($records) implements DnsQueryInterface {
            public array $calls = [];

            public function __construct(private readonly array $records) {}

            public function query(string $name, int $type): DnsAnswer
            {
                $this->calls[] = [$name, $type];

                return new DnsAnswer(
                    count($this->calls) === 1 ? $this->records : [['host' => rtrim($name, '.'), 'type' => 'A', 'ttl' => 10, 'ip' => '8.8.8.8']],
                    'dns',
                    true,
                );
            }
        };
        try {
            (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, $this->clock()))->resolve('root.example');
            self::fail('Unverifiable alias produced a resolution');
        } catch (PolicyException $error) {
            self::assertSame('resolution_unverified', $error->reasonCode());
        }
        self::assertSame(
            [['root.example.', 1]],
            $query->calls,
            'A rejected alias answer must not trigger more DNS traffic',
        );
    }

    public static function invalidAliasAnswers(): iterable
    {
        foreach (['127.0.0.1', '::1'] as $target) {
            yield 'literal target ' . $target => [[['host' => 'root.example', 'type' => 'CNAME', 'ttl' => 5, 'target' => $target]]];
        }
        yield 'conflicting aliases' => [
            [
                ['host' => 'root.example', 'type' => 'CNAME', 'ttl' => 5, 'target' => 'first.example'],
                ['host' => 'root.example', 'type' => 'CNAME', 'ttl' => 5, 'target' => 'second.example'],
            ],
        ];
    }

    public function testAliasAndAddressConflictStopsBeforeQueryingTheAlias(): void
    {
        $query = new class implements DnsQueryInterface {
            public array $calls = [];

            public function query(string $name, int $type): DnsAnswer
            {
                $this->calls[] = [$name, $type];
                $records       = $name === 'root.example.' && $type === 1 ? [
                    ['host' => 'root.example', 'type' => 'CNAME', 'ttl' => 5, 'target' => 'alias.example'],
                    ['host' => 'root.example', 'type' => 'A', 'ttl' => 5, 'ip' => '8.8.8.8'],
                ] : ($name === 'alias.example.' ? [['host' => 'alias.example', 'type' => 'A', 'ttl' => 5, 'ip' => '8.8.4.4']] : []);

                return new DnsAnswer($records, 'dns', true);
            }
        };
        try {
            (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, $this->clock()))->resolve('root.example');
            self::fail('Alias and address conflict produced a resolution');
        } catch (PolicyException $error) {
            self::assertSame('resolution_unverified', $error->reasonCode());
        }
        self::assertSame([['root.example.', 1], ['root.example.', 28], ['root.example.', 5]], $query->calls);
    }

    #[DataProvider('duplicateRecordKinds')]
    public function testLaterDuplicateCannotRestartAnEarlierAbsoluteExpiry(bool $alias, int $remaining): void
    {
        $clock = $this->clock();
        $query = new class ($clock, $alias) implements DnsQueryInterface {
            public function __construct(private readonly ClockInterface $clock, private readonly bool $alias) {}

            public function query(string $name, int $type): DnsAnswer
            {
                $this->clock->elapsed += 0.5;
                $records = [];
                if ($name === 'root.example.' && in_array($type, [1, 28], true)) {
                    $records[] = ['host' => 'root.example', 'type' => $this->alias ? 'CNAME' : 'A', 'ttl' => $type === 1 ? 5 : 20] + ($this->alias ? ['target' => 'alias.example'] : ['ip' => '8.8.8.8']);
                } elseif ($name === 'alias.example.' && $type === 1) {
                    $records[] = ['host' => 'alias.example', 'type' => 'A', 'ttl' => 60, 'ip' => '8.8.8.8'];
                }

                return new DnsAnswer($records, 'dns', true);
            }
        };
        $answer = (new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, $clock))->resolve('root.example');
        self::assertSame(['8.8.8.8'], $answer->addresses);
        self::assertSame(
            $remaining,
            $answer->ttlSeconds,
            'Elapsed time starts at the earliest answer, including duplicate records',
        );
    }

    public static function duplicateRecordKinds(): iterable
    {
        yield 'CNAME duplicate' => [true, 2];
        yield 'address duplicate' => [false, 3];
    }

    private function clock(): ClockInterface
    {
        return new class implements ClockInterface {
            public float $elapsed = 0;

            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-10-18T00:00:00Z');
            }

            public function monotonic(): float
            {
                return $this->elapsed;
            }
        };
    }
}
