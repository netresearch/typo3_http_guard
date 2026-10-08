<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{GuardConfig, DecisionReporter, DecisionEvent, ClockInterface};
use PHPUnit\Framework\TestCase;
final class TelemetryTestClock implements ClockInterface
{
    public float $seconds = 0;
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-10-08T00:00:00Z');
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
            'api.example'
        );
    }
    public function testHashRequiresConfiguredKeyAndNoSensitiveFieldsAreAccepted(): void
    {
        $rows = [];
        $clock = new TelemetryTestClock();
        $logger = static function (array $row) use (&$rows): void {
            $rows[] = $row;
        };
        $reporter = new DecisionReporter(GuardConfig::fromArray([]), $clock, $logger);
        $reporter->report($this->event());
        self::assertArrayNotHasKey('host', $rows[0]);
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(
                ['logging' => ['hostHmacKeyEnv' => 'GUARD_HOST_KEY']]
            ),
            $clock,
            $logger,
            'operator key'
        );
        $reporter->report($this->event());
        self::assertSame(
            hash_hmac('sha256', 'api.example', 'operator key'),
            $rows[1]['host']
        );
        self::assertArrayNotHasKey('request', $rows[1]);
        self::assertArrayNotHasKey('headers', $rows[1]);
        self::assertArrayNotHasKey('query', $rows[1]);
        self::assertArrayNotHasKey('path', $rows[1]);
    }
    public function testDenyLoggingLimitNeverLosesCountersAndLogFailureDoesNotEscape(): void
    {
        $clock = new TelemetryTestClock();
        $rows = [];
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(
                ['logging' => ['denyRateLimitPerMinute' => 1]]
            ),
            $clock,
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            }
        );
        $reporter->report($this->event());
        $reporter->report($this->event());
        self::assertCount(1, $rows);
        self::assertSame(
            2,
            array_sum(array_column($reporter->metrics(), 'count'))
        );
        $clock->seconds = 60;
        $reporter->report($this->event());
        self::assertCount(2, $rows);
        $broken = new DecisionReporter(
            GuardConfig::fromArray([]),
            $clock,
            static function (): void {
                throw new \RuntimeException('sensitive provider error');
            }
        );
        $broken->report($this->event());
        self::assertSame(
            1,
            array_sum(array_column($broken->metrics(), 'count'))
        );
        self::assertSame(1, $broken->loggerFailureCount());
    }
    public function testObserveEvaluationReportsOneSafeWouldDenyEvent(): void
    {
        $config = \Netresearch\HttpGuard\GuardConfig::fromArray(['mode' => 'observe']);
        $clock = new \Netresearch\HttpGuard\SystemClock();
        $registry = new \Netresearch\HttpGuard\PolicyRegistry($config, $clock);
        $reporter = new class implements \Netresearch\HttpGuard\DecisionReporterInterface
        {
            public array $events = [];
            public function report(DecisionEvent $event): void
            {
                $this->events[] = $event;
            }
        };
        $resolver = new class implements \Netresearch\HttpGuard\ResolverInterface
        {
            public function resolve(
                string $host
            ): \Netresearch\HttpGuard\Resolution
            {
                throw new \LogicException('literal must skip DNS');
            }
        };
        $engine = new \Netresearch\HttpGuard\PolicyEngine(
            new \Netresearch\HttpGuard\TargetNormalizer(),
            new \Netresearch\HttpGuard\AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            $reporter
        );
        $decision = $engine->evaluate(
            new \GuzzleHttp\Psr7\Request(
                'GET',
                'https://10.23.4.12/secret?credential=never-log'
            ),
            $registry->newContext()
        );
        self::assertSame('would_deny', $decision->decision);
        self::assertCount(1, $reporter->events);
        self::assertSame('literal', $reporter->events[0]->resolverSource);
        self::assertSame('private_ipv4', $reporter->events[0]->addressClass);
        self::assertSame('10.23.4.12', $reporter->events[0]->host);
        self::assertStringNotContainsString(
            'credential',
            json_encode($reporter->events[0], JSON_THROW_ON_ERROR)
        );
    }
}
