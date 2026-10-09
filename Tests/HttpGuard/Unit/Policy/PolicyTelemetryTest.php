<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use DateTimeImmutable;
use LogicException;
use Netresearch\HttpGuard\{ClockInterface, DecisionEvent, DecisionReporter, GuardConfig};
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TelemetryTestClock implements ClockInterface
{
    public float $seconds = 0;

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-08T00:00:00Z');
    }

    public function monotonic(): float
    {
        return $this->seconds;
    }
}
final class PolicyTelemetryTest extends TestCase
{
    private function event(): DecisionEvent
    {
        return new DecisionEvent(
            1,
            '2026-10-08T00:00:00Z',
            'enforce',
            'deny',
            'address_forbidden',
            null,
            str_repeat('a', 64),
            'private_ipv4',
            'https',
            443,
            'dns',
            str_repeat('b', 24),
            'api.example',
        );
    }

    public function testHashRequiresConfiguredKeyAndNoSensitiveFieldsAreAccepted(): void
    {
        $rows   = [];
        $clock  = new TelemetryTestClock();
        $logger = static function (array $row) use (&$rows): void {
            $rows[] = $row;
        };
        $reporter = new DecisionReporter(GuardConfig::fromArray([]), $clock, $logger);
        $reporter->report($this->event());
        self::assertArrayNotHasKey('host', $rows[0]);
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['hostHmacKeyEnv' => 'GUARD_HOST_KEY']]),
            $clock,
            $logger,
            'operator key',
        );
        $reporter->report($this->event());
        self::assertSame(hash_hmac('sha256', 'api.example', 'operator key'), $rows[1]['host']);
        self::assertArrayNotHasKey('request', $rows[1]);
        self::assertArrayNotHasKey('headers', $rows[1]);
        self::assertArrayNotHasKey('query', $rows[1]);
        self::assertArrayNotHasKey('path', $rows[1]);
    }

    public function testDenyLoggingLimitNeverLosesCountersAndLogFailureDoesNotEscape(): void
    {
        $clock    = new TelemetryTestClock();
        $rows     = [];
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['denyRateLimitPerMinute' => 1]]),
            $clock,
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
        );
        $reporter->report($this->event());
        $reporter->report($this->event());
        self::assertCount(1, $rows);
        self::assertSame(2, array_sum(array_column($reporter->metrics(), 'count')));
        $clock->seconds = 60;
        $reporter->report($this->event());
        self::assertCount(2, $rows);
        $broken = new DecisionReporter(
            GuardConfig::fromArray([]),
            $clock,
            static function (): void {
                throw new RuntimeException('sensitive provider error');
            },
        );
        $broken->report($this->event());
        self::assertSame(1, array_sum(array_column($broken->metrics(), 'count')));
        self::assertSame(1, $broken->loggerFailureCount());
    }

    public function testObserveEvaluationReportsOneSafeWouldDenyEvent(): void
    {
        $config   = GuardConfig::fromArray(['mode' => 'observe']);
        $clock    = new \Netresearch\HttpGuard\SystemClock();
        $registry = new \Netresearch\HttpGuard\PolicyRegistry($config, $clock);
        $reporter = new class implements \Netresearch\HttpGuard\DecisionReporterInterface {
            public array $events = [];

            public function report(DecisionEvent $event): void
            {
                $this->events[] = $event;
            }
        };
        $resolver = new class implements \Netresearch\HttpGuard\ResolverInterface {
            public function resolve(string $host): \Netresearch\HttpGuard\Resolution
            {
                throw new LogicException('literal must skip DNS');
            }
        };
        $engine = new \Netresearch\HttpGuard\PolicyEngine(
            new \Netresearch\HttpGuard\TargetNormalizer(),
            new \Netresearch\HttpGuard\AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            $reporter,
        );
        $decision = $engine->evaluate(
            new \GuzzleHttp\Psr7\Request('GET', 'https://10.23.4.12/secret?credential=never-log'),
            $registry->newContext(),
        );
        self::assertSame('would_deny', $decision->decision);
        self::assertCount(1, $reporter->events);
        self::assertSame('literal', $reporter->events[0]->resolverSource);
        self::assertSame('private_ipv4', $reporter->events[0]->addressClass);
        self::assertSame('10.23.4.12', $reporter->events[0]->host);
        self::assertStringNotContainsString('credential', json_encode($reporter->events[0], JSON_THROW_ON_ERROR));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('boundedFields')]
    public function testTelemetryKeepsOnlyValidatedBoundedFields(
        ?string $addressClass,
        ?string $scheme,
        ?int $port,
        ?string $source,
        string $correlation,
        ?string $expectedClass,
        ?string $expectedScheme,
        ?int $expectedPort,
        ?string $expectedSource,
        ?string $expectedCorrelation,
    ): void {
        $clock  = new TelemetryTestClock();
        $rows   = [];
        $config = GuardConfig::fromArray(
            [
                'logging'   => ['allowedSampleRate' => 1],
                'endpoints' => [
                    'trusted' => [
                        'origin'       => 'https://api.example',
                        'allowedCidrs' => ['10.0.0.0/24'],
                        'methods'      => ['GET'],
                        'purpose'      => 'telemetry fixture',
                        'owner'        => 'test maintainers',
                    ],
                ],
            ],
        );
        $reporter = new DecisionReporter(
            $config,
            $clock,
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
        );
        $reporter->report(
            new DecisionEvent(
                999,
                'untrusted time',
                'observe',
                'would_deny',
                'address_forbidden',
                'trusted',
                'untrusted revision',
                $addressClass,
                $scheme,
                $port,
                $source,
                $correlation,
                null,
            ),
        );
        self::assertSame(
            [
                [
                    'version'        => 1,
                    'time'           => '2026-10-08T00:00:00.000+00:00',
                    'mode'           => 'observe',
                    'decision'       => 'would_deny',
                    'reasonCode'     => 'address_forbidden',
                    'profileId'      => 'trusted',
                    'policyRevision' => $config->revision,
                    'addressClass'   => $expectedClass,
                    'scheme'         => $expectedScheme,
                    'port'           => $expectedPort,
                    'resolverSource' => $expectedSource,
                    'correlationId'  => $expectedCorrelation,
                ],
            ],
            $rows,
        );
        self::assertSame(
            [
                [
                    'labels' => [
                        'mode'       => 'observe',
                        'decision'   => 'would_deny',
                        'reasonCode' => 'address_forbidden',
                        'profileId'  => 'trusted',
                    ],
                    'count' => 1,
                ],
            ],
            $reporter->metrics(),
        );
    }

    /** @return iterable<string,array{?string,?string,?int,?string,string,?string,?string,?int,?string,?string}> */
    public static function boundedFields(): iterable
    {
        $short = str_repeat('a', 24);
        $long  = str_repeat('b', 64);
        yield 'minimums' => ['a', 'http', 1, 'd', $short, 'a', 'http', 1, 'd', $short];
        yield 'maximums' => [
            str_repeat('x', 64),
            'https',
            65535,
            str_repeat('r', 64),
            $long,
            str_repeat('x', 64),
            'https',
            65535,
            str_repeat('r', 64),
            $long,
        ];
        yield 'optional fields absent' => [null, null, null, null, $short, null, null, null, null, $short];
        yield 'empty labels' => ['', 'ftp', 0, '', '', null, null, null, null, null];
        yield 'above maximums' => [str_repeat('x', 65), 'HTTPS', 65536, str_repeat('r', 65), str_repeat('a', 65), null, null, null, null, null];
        yield 'below correlation minimum' => ['private_ipv4', 'https', 443, 'dns', str_repeat('a', 23), 'private_ipv4', 'https', 443, 'dns', null];
        yield 'newline suffixes' => ["private_ipv4\n", 'https', 443, "dns\n", $short . "\n", null, 'https', 443, null, null];
        yield 'newline prefixes' => ["\nprivate_ipv4", 'https', 443, "\ndns", "\n" . $short, null, 'https', 443, null, null];
        yield 'invalid characters' => ['private/ipv4', 'file', -1, 'dns/secret', str_repeat('A', 24), null, null, null, null, null];
        yield 'permitted punctuation' => ['A_+1-', 'https', 443, 'D_+2-', $short, 'A_+1-', 'https', 443, 'D_+2-', $short];
    }

    public function testUnknownLabelsHaveOneFiniteFallbackMetricSeries(): void
    {
        $config   = GuardConfig::fromArray([]);
        $clock    = new TelemetryTestClock();
        $rows     = [];
        $reporter = new DecisionReporter(
            $config,
            $clock,
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
        );
        for ($i = 0; $i < 10; ++$i) {
            $reporter->report(
                new DecisionEvent(
                    1,
                    'ignored',
                    'unknown-mode-' . $i,
                    'unknown-decision-' . $i,
                    'unknown-reason-' . $i,
                    'unknown-profile-' . $i,
                    'ignored',
                    null,
                    null,
                    null,
                    null,
                    str_repeat('a', 24),
                    null,
                ),
            );
        }
        self::assertSame(
            [
                [
                    'labels' => [
                        'mode'       => 'enforce',
                        'decision'   => 'unverifiable',
                        'reasonCode' => 'configuration_invalid',
                        'profileId'  => null,
                    ],
                    'count' => 10,
                ],
            ],
            $reporter->metrics(),
        );
        self::assertCount(10, $rows);
        foreach ($rows as $row) {
            self::assertSame('enforce', $row['mode']);
            self::assertSame('unverifiable', $row['decision']);
            self::assertSame('configuration_invalid', $row['reasonCode']);
            self::assertNull($row['profileId']);
        }
    }

    public function testDenyWindowStartsAtCurrentClockAndResetsExactlyAtSixtySeconds(): void
    {
        $clock          = new TelemetryTestClock();
        $clock->seconds = 100;
        $rows           = [];
        $reporter       = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['denyRateLimitPerMinute' => 2]]),
            $clock,
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
        );
        $reporter->report($this->event());
        $reporter->report($this->event());
        $reporter->report($this->event());
        self::assertCount(2, $rows);
        $clock->seconds = 159.999;
        $reporter->report($this->event());
        self::assertCount(2, $rows);
        $clock->seconds = 160;
        $reporter->report($this->event());
        $reporter->report($this->event());
        $reporter->report($this->event());
        self::assertCount(4, $rows);
        self::assertSame(7, array_sum(array_column($reporter->metrics(), 'count')));
    }
}
