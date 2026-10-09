<?php

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Closure;
use DateTimeImmutable;
use Error;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\{AddressClassifier, ClockInterface, DnsAnswer, DnsQueryInterface, GuardConfig, NullDecisionReporter, PolicyEngine, PolicyException, PolicyRegistry, Resolution, ResolverInterface, StaticThenDnsResolver, TargetNormalizer};
use PHPUnit\Framework\TestCase;

final class PolicyTestClock implements ClockInterface
{
    public float $elapsed = 0;

    public function now(): DateTimeImmutable
    {
        return (new DateTimeImmutable('2026-10-08T00:00:00Z'))->modify('+' . (int) $this->elapsed . ' seconds');
    }

    public function monotonic(): float
    {
        return $this->elapsed;
    }
}
final class PolicyTestResolver implements ResolverInterface
{
    public array $addresses = ['10.23.4.12'];
    public int $calls       = 0;
    public ?Closure $after  = null;

    public function resolve(string $host): Resolution
    {
        ++$this->calls;
        if ($this->after) {
            ($this->after)();
        }

        return new Resolution($this->addresses, 'fixture', 5, (string) $this->calls);
    }
}
final class PolicyTestQuery implements DnsQueryInterface
{
    public array $records = [];
    public int $calls     = 0;

    public function query(string $host, int $qtype): DnsAnswer
    {
        ++$this->calls;
        if ($this->after) {
            ($this->after)();
        }

        return new DnsAnswer($this->records[$host][$qtype] ?? [], 'fixture', true);
    }
    public ?Closure $after = null;
}
final class PolicyLifecycleTest extends TestCase
{
    private function config(array $extra = []): GuardConfig
    {
        return GuardConfig::fromArray(
            array_replace_recursive(
                [
                    'endpoints' => [
                        'erp' => [
                            'origin'       => 'https://erp.internal:8443',
                            'allowedCidrs' => ['10.23.4.0/24'],
                            'methods'      => ['POST'],
                            'purpose'      => 'orders',
                            'owner'        => 'ERP maintainers',
                            'expiresAt'    => '2026-10-08T00:00:02Z',
                        ],
                    ],
                ],
                $extra,
            ),
        );
    }

    private function engine(GuardConfig $config, PolicyTestClock $clock, PolicyTestResolver $resolver): array
    {
        $registry = new PolicyRegistry($config, $clock);

        return [
            new PolicyEngine(
                new TargetNormalizer(),
                new AddressClassifier(),
                $resolver,
                $registry,
                $clock,
                new NullDecisionReporter(),
            ),
            $registry,
        ];
    }

    public function testPublicDoesNotInheritConfiguredEndpoint(): void
    {
        $clock               = new PolicyTestClock();
        $resolver            = new PolicyTestResolver();
        [$engine, $registry] = $this->engine($this->config(), $clock, $resolver);
        self::assertSame(
            'address_forbidden',
            $engine->evaluate(new Request('POST', 'https://erp.internal:8443'), $registry->newContext())->reasonCode,
        );
        $plan = $engine->plan(new Request('POST', 'https://erp.internal:8443'), $registry->newContext('erp'));
        self::assertSame(['10.23.4.12'], $plan->addresses);
    }

    public function testPostOnlyHostPreflightDoesNotInventMethodOrPort(): void
    {
        $clock               = new PolicyTestClock();
        [$engine, $registry] = $this->engine($this->config(), $clock, new PolicyTestResolver());
        self::assertTrue($engine->hostAllowed('ERP.INTERNAL.', $registry->newContext('erp')));
        self::assertFalse($engine->hostAllowed('another.internal', $registry->newContext('erp')));
    }

    public function testFinalStartRechecksExpiryWithoutSecondDns(): void
    {
        $clock               = new PolicyTestClock();
        $resolver            = new PolicyTestResolver();
        [$engine, $registry] = $this->engine($this->config(), $clock, $resolver);
        $context             = $registry->newContext('erp');
        $plan                = $engine->plan(new Request('POST', 'https://erp.internal:8443'), $context);
        $clock->elapsed      = 2;
        try {
            $engine->assertCurrent($plan, $context);
            self::fail('Expired plan accepted');
        } catch (PolicyException $e) {
            self::assertSame('grant_invalid', $e->reasonCode());
        }
        self::assertSame(1, $resolver->calls);
    }

    public function testExpiryDuringBlockingDnsNeverIssuesUsablePlan(): void
    {
        $clock           = new PolicyTestClock();
        $resolver        = new PolicyTestResolver();
        $resolver->after = static function () use ($clock): void {
            $clock->elapsed = 2;
        };
        [$engine, $registry] = $this->engine($this->config(), $clock, $resolver);
        self::assertSame(
            'grant_invalid',
            $engine->evaluate(new Request('POST', 'https://erp.internal:8443'), $registry->newContext('erp'))->reasonCode,
        );
    }

    public function testForeignRegistryOrClientPlanIsRejected(): void
    {
        $clock               = new PolicyTestClock();
        $config              = $this->config();
        [$engine, $registry] = $this->engine($config, $clock, new PolicyTestResolver());
        $foreign             = (new PolicyRegistry($config, $clock))->newContext('erp');
        self::assertSame(
            'grant_invalid',
            $engine->evaluate(new Request('POST', 'https://erp.internal:8443'), $foreign)->reasonCode,
        );
        $context = $registry->newContext('erp');
        $plan    = $engine->plan(new Request('POST', 'https://erp.internal:8443'), $context);
        $this->expectException(PolicyException::class);
        $engine->assertCurrent($plan, $registry->newContext('erp'));
    }

    public function testMetadataAndOperatorDenyOverrideEndpoint(): void
    {
        foreach (['fd00:ec2::254', '100.100.100.200', '168.63.129.16'] as $ip) {
            $clock               = new PolicyTestClock();
            $resolver            = new PolicyTestResolver();
            $resolver->addresses = [$ip];
            $cidr                = str_contains($ip, ':') ? $ip . '/128' : $ip . '/32';
            $config              = $this->config(['endpoints' => ['erp' => ['allowedCidrs' => [$cidr]]]]);
            [$engine, $registry] = $this->engine($config, $clock, $resolver);
            self::assertSame(
                'address_forbidden',
                $engine->evaluate(new Request('POST', 'https://erp.internal:8443'), $registry->newContext('erp'))->reasonCode,
            );
        }
        $clock               = new PolicyTestClock();
        [$engine, $registry] = $this->engine($this->config(['deniedCidrs' => ['10.23.4.12/32']]), $clock, new PolicyTestResolver());
        self::assertSame(
            'address_forbidden',
            $engine->evaluate(new Request('POST', 'https://erp.internal:8443'), $registry->newContext('erp'))->reasonCode,
        );
    }

    public function testMixedPublicPrivateDnsCannotFilterPrivateAnswer(): void
    {
        $clock               = new PolicyTestClock();
        $resolver            = new PolicyTestResolver();
        $resolver->addresses = ['8.8.8.8', 'fd00::1'];
        [$engine, $registry] = $this->engine(GuardConfig::fromArray([]), $clock, $resolver);
        self::assertSame(
            'address_forbidden',
            $engine->evaluate(new Request('GET', 'https://public.example'), $registry->newContext())->reasonCode,
        );
    }

    public function testCompleteCnameChainMinimumTtlAndBoundedPositiveCache(): void
    {
        $clock          = new PolicyTestClock();
        $query          = new PolicyTestQuery();
        $query->records = [
            'api.example.'   => [5 => [['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => 'alias.example']]],
            'alias.example.' => [1 => [['host' => 'alias.example', 'type' => 'A', 'ttl' => 120, 'ip' => '8.8.8.8']]],
        ];
        $resolver = new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, $clock);
        $first    = $resolver->resolve('api.example');
        self::assertSame(['8.8.8.8'], $first->addresses);
        self::assertSame(1, $first->ttlSeconds);
        self::assertSame(['alias.example'], $first->cnameChain);
        self::assertSame(6, $query->calls);
        self::assertSame($first, $resolver->resolve('api.example'));
        self::assertSame(6, $query->calls);
        $clock->elapsed = 1;
        $resolver->resolve('api.example');
        self::assertSame(12, $query->calls);
    }

    public function testCnameCyclesAndForeignOwnerAnswersFailClosed(): void
    {
        $clock          = new PolicyTestClock();
        $query          = new PolicyTestQuery();
        $query->records = [
            'a.example.' => [5 => [['host' => 'a.example', 'type' => 'CNAME', 'ttl' => 2, 'target' => 'b.example']]],
            'b.example.' => [5 => [['host' => 'b.example', 'type' => 'CNAME', 'ttl' => 2, 'target' => 'a.example']]],
        ];
        $resolver = new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, $clock);
        try {
            $resolver->resolve('a.example');
            self::fail('cycle accepted');
        } catch (PolicyException $e) {
            self::assertSame('resolution_limit', $e->reasonCode());
        }
        $query->records = ['safe.example.' => [1 => [['host' => 'wrong.example', 'type' => 'A', 'ttl' => 2, 'ip' => '8.8.8.8']]]];
        try {
            $resolver->resolve('safe.example');
            self::fail('foreign record accepted');
        } catch (PolicyException $e) {
            self::assertSame('resolution_unverified', $e->reasonCode());
        }
    }

    public function testGrantCannotSerializeOrClone(): void
    {
        $context = (new PolicyRegistry($this->config(), new PolicyTestClock()))->newContext('erp');
        try {
            serialize($context->endpointGrant);
            self::fail('grant serialized');
        } catch (PolicyException $e) {
            self::assertSame('grant_invalid', $e->reasonCode());
        }
        $this->expectException(Error::class);
        clone $context->endpointGrant;
    }

    public function testDnsCacheDoesNotExtendTtlByTimeSpentInLaterQueries(): void
    {
        $clock        = new PolicyTestClock();
        $query        = new PolicyTestQuery();
        $query->after = static function () use ($clock): void {
            $clock->elapsed += 0.5;
        };
        $query->records = [
            'api.example.'   => [5 => [['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 1, 'target' => 'alias.example']]],
            'alias.example.' => [1 => [['host' => 'alias.example', 'type' => 'A', 'ttl' => 120, 'ip' => '8.8.8.8']]],
        ];
        $resolver   = new StaticThenDnsResolver(GuardConfig::fromArray([]), $query, $clock);
        $resolution = $resolver->resolve('api.example');
        self::assertSame(0, $resolution->ttlSeconds);
        $resolver->resolve('api.example');
        self::assertSame(12, $query->calls);
    }

    public function testMemoHitReauthorizesChangedPolicyAndRejectsOldRevisionContext(): void
    {
        $clock          = new PolicyTestClock();
        $query          = new PolicyTestQuery();
        $query->records = ['memo.example.' => [1 => [['host' => 'memo.example', 'type' => 'A', 'ttl' => 120, 'ip' => '8.8.8.8']]]];
        $initial        = GuardConfig::fromArray([]);
        $resolver       = new StaticThenDnsResolver($initial, $query, $clock);
        $beforeRegistry = new PolicyRegistry($initial, $clock);
        $oldContext     = $beforeRegistry->newContext();
        $beforeEngine   = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $beforeRegistry,
            $clock,
            new NullDecisionReporter(),
        );
        $request = new Request('GET', 'https://memo.example');
        self::assertSame('allow', $beforeEngine->evaluate($request, $oldContext)->decision);
        self::assertSame(3, $query->calls);
        $changed = GuardConfig::fromArray(['deniedCidrs' => ['8.8.8.8/32']]);
        self::assertNotSame($initial->revision, $changed->revision);
        $newRegistry = new PolicyRegistry($changed, $clock);
        $newEngine   = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $newRegistry,
            $clock,
            new NullDecisionReporter(),
        );
        self::assertSame('address_forbidden', $newEngine->evaluate($request, $newRegistry->newContext())->reasonCode);
        self::assertSame(3, $query->calls);
        self::assertSame('grant_invalid', $newEngine->evaluate($request, $oldContext)->reasonCode);
        self::assertSame(3, $query->calls);
    }
}
