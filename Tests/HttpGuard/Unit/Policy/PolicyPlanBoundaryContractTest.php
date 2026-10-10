<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use DateTimeImmutable;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\{AddressClassifier, ClockInterface, DecisionEvent, DecisionReporterInterface, GuardConfig, PolicyEngine, PolicyException, PolicyRegistry, Resolution, ResolverInterface, TargetNormalizer};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PolicyBoundaryClock implements ClockInterface
{
    public int $seconds = 0;

    public function now(): DateTimeImmutable
    {
        return (new DateTimeImmutable('2026-10-09T00:00:00Z'))->modify('+' . $this->seconds . ' seconds');
    }

    public function monotonic(): float
    {
        return $this->seconds;
    }
}
final class PolicyBoundaryResolver implements ResolverInterface
{
    public array $addresses                  = ['203.0.115.7'];
    public array $calls                      = [];
    public ?PolicyBoundaryClock $expireClock = null;

    public function resolve(string $host): Resolution
    {
        $this->calls[] = $host;
        if ($this->expireClock instanceof PolicyBoundaryClock) {
            $this->expireClock->seconds = 2;
        }

        return new Resolution($this->addresses, 'fixture', 5, 'fixture-generation');
    }
}
final class PolicyBoundaryReporter implements DecisionReporterInterface
{
    public array $events = [];

    public function report(DecisionEvent $event): void
    {
        $this->events[] = $event;
    }
}
final class PolicyPlanBoundaryContractTest extends TestCase
{
    private function setupEngine(
        GuardConfig $config,
        ?PolicyBoundaryResolver $resolver = null,
        ?PolicyBoundaryClock $clock = null,
    ): array {
        $resolver ??= new PolicyBoundaryResolver();
        $clock ??= new PolicyBoundaryClock();
        $registry = new PolicyRegistry($config, $clock);
        $reporter = new PolicyBoundaryReporter();

        return [
            new PolicyEngine(new TargetNormalizer(), new AddressClassifier(), $resolver, $registry, $clock, $reporter),
            $registry,
            $resolver,
            $reporter,
        ];
    }

    public function testPlanAndEvaluationEachReportOneCorrectAllowEvent(): void
    {
        [$engine, $registry, $resolver, $reporter] = $this->setupEngine(GuardConfig::fromArray([]));
        $context                                   = $registry->newContext();
        $request                                   = new Request('GET', 'https://public.example/path?private=value');
        $plan                                      = $engine->plan($request, $context);
        self::assertSame(['203.0.115.7'], $plan->addresses);
        self::assertSame('fixture-generation', $plan->resolverGeneration);
        self::assertSame('allow', $engine->evaluate($request, $context)->decision);
        self::assertCount(2, $reporter->events);
        foreach ($reporter->events as $event) {
            self::assertSame(1, $event->version);
            self::assertSame('allow', $event->decision);
            self::assertNull($event->reasonCode);
            self::assertSame('fixture', $event->resolverSource);
            self::assertSame('ordinary_global_unicast', $event->addressClass);
            self::assertSame('public.example', $event->host);
        }
    }

    public function testDeniedPlanStillReportsExactlyOneReasonBeforeRethrowing(): void
    {
        [$engine, $registry, $resolver, $reporter] = $this->setupEngine(GuardConfig::fromArray([]));
        try {
            $engine->plan(new Request('GET', 'http://10.23.4.12'), $registry->newContext());
            self::fail('Private public-client request accepted');
        } catch (PolicyException $exception) {
            self::assertSame('address_forbidden', $exception->reasonCode());
        }
        self::assertSame([], $resolver->calls);
        self::assertCount(1, $reporter->events);
        self::assertSame('deny', $reporter->events[0]->decision);
        self::assertSame('address_forbidden', $reporter->events[0]->reasonCode);
    }

    public function testHostPreflightValidatesEveryAddressAndUsesLiteralWithoutDns(): void
    {
        [$engine, $registry, $resolver] = $this->setupEngine(GuardConfig::fromArray([]));
        $context                        = $registry->newContext();
        self::assertTrue($engine->hostAllowed('1api.example', $context));
        self::assertTrue($engine->hostAllowed('api.example1', $context));
        self::assertTrue($engine->hostAllowed('203.0.115.7', $context));
        self::assertTrue($engine->hostAllowed('2001:4860::8888', $context));
        self::assertFalse($engine->hostAllowed('10.23.4.12', $context));
        self::assertFalse($engine->hostAllowed('bad_host', $context));
        self::assertSame(['1api.example', 'api.example1'], $resolver->calls);
        $resolver->addresses = ['203.0.115.7', '10.23.4.12'];
        self::assertFalse($engine->hostAllowed('mixed.example', $context));
    }

    #[DataProvider('invalidAddressSets')]
    public function testCompleteAddressListIsMandatoryAndHasBoundedSize(array $addresses, string $reason): void
    {
        $resolver            = new PolicyBoundaryResolver();
        $resolver->addresses = $addresses;
        [$engine, $registry] = $this->setupEngine(GuardConfig::fromArray(['resolver' => ['maxAddresses' => 1]]), $resolver);
        self::assertSame(
            $reason,
            $engine->evaluate(new Request('GET', 'https://public.example'), $registry->newContext())->reasonCode,
        );
    }

    public static function invalidAddressSets(): iterable
    {
        yield 'empty' => [[], 'resolution_unverified'];
        yield 'associative' => [['one' => '203.0.115.7'], 'resolution_unverified'];
        yield 'wrong element type' => [[false], 'resolution_unverified'];
        yield 'malformed address' => [['bad-address'], 'resolution_unverified'];
        yield 'too many' => [['203.0.115.7', '203.0.115.8'], 'resolution_limit'];
    }

    public function testMaximumAddressCountAndMappedDuplicatesKeepDenseCanonicalPlan(): void
    {
        $resolver            = new PolicyBoundaryResolver();
        $resolver->addresses = ['203.0.115.7', '::ffff:203.0.115.7', '203.0.115.8'];
        [$engine, $registry] = $this->setupEngine(GuardConfig::fromArray(['resolver' => ['maxAddresses' => 3]]), $resolver);
        self::assertSame(
            ['203.0.115.7', '203.0.115.8'],
            $engine->plan(new Request('GET', 'https://public.example'), $registry->newContext())->addresses,
        );
    }

    public function testMixedCaseConnectIsRejectedBeforeDns(): void
    {
        [$engine, $registry, $resolver] = $this->setupEngine(GuardConfig::fromArray([]));
        self::assertSame(
            'invalid_target',
            $engine->evaluate(new Request('cOnNeCt', 'https://public.example'), $registry->newContext())->reasonCode,
        );
        self::assertSame([], $resolver->calls);
    }

    public function testEndpointHostPreflightRechecksExpiryAfterBlockingResolver(): void
    {
        $clock                 = new PolicyBoundaryClock();
        $resolver              = new PolicyBoundaryResolver();
        $resolver->addresses   = ['10.23.4.12'];
        $resolver->expireClock = $clock;
        $config                = GuardConfig::fromArray(
            [
                'endpoints' => [
                    'api' => [
                        'origin'       => 'https://api.example',
                        'allowedCidrs' => ['10.23.4.0/24'],
                        'methods'      => ['GET'],
                        'purpose'      => 'Business contract',
                        'owner'        => 'Application maintainers',
                        'expiresAt'    => '2026-10-09T00:00:02Z',
                    ],
                ],
            ],
        );
        [$engine, $registry] = $this->setupEngine($config, $resolver, $clock);
        self::assertFalse($engine->hostAllowed('api.example', $registry->newContext('api')));
        self::assertSame(['api.example'], $resolver->calls);
    }
}
