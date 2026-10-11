<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\HttpGuard\Transport\TransferDriver;
use Netresearch\HttpGuard\Transport\TransferLease;
use PHPUnit\Framework\TestCase;
use stdClass;
use WeakReference;

final class IdleTransferLeaseTest extends TestCase
{
    public function testCancellationBeforeTickReleasesExactlyOnceWithoutNativeConstruction(): void
    {
        [$engine, $registry] = $this->policy();
        $driver              = new TransferDriver();
        $lease               = new TransferLease($driver, $engine, $registry->newContext(), new Request('GET', 'https://api.example'), []);
        self::assertFalse($lease->settled());
        self::assertSame(
            ['created' => 1, 'released' => 0, 'active' => 1, 'peak' => 1, 'nativeConstructed' => 0],
            $driver->counters(),
        );
        $lease->promise()->cancel();
        $lease->promise()->cancel();
        $driver->tick();
        $driver->waitFor($lease);
        self::assertTrue($lease->settled());
        self::assertSame(PromiseInterface::REJECTED, $lease->promise()->getState());
        self::assertSame(
            ['created' => 1, 'released' => 1, 'active' => 0, 'peak' => 1, 'nativeConstructed' => 0],
            $driver->counters(),
        );
    }

    public function testIndependentIdleLeasesReleaseWithoutDisturbingTheOtherLease(): void
    {
        [$engine, $registry] = $this->policy();
        $driver              = new TransferDriver();
        $context             = $registry->newContext();
        $first               = new TransferLease($driver, $engine, $context, new Request('GET', 'https://api.example/first'), []);
        $second              = new TransferLease($driver, $engine, $context, new Request('GET', 'https://api.example/second'), []);
        self::assertSame(2, $driver->counters()['active']);
        self::assertSame(2, $driver->counters()['peak']);
        $first->promise()->cancel();
        self::assertTrue($first->settled());
        self::assertFalse($second->settled());
        self::assertSame(1, $driver->counters()['active']);
        self::assertSame(1, $driver->counters()['released']);
        $second->promise()->cancel();
        self::assertSame(
            ['created' => 2, 'released' => 2, 'active' => 0, 'peak' => 2, 'nativeConstructed' => 0],
            $driver->counters(),
        );
    }

    public function testCancelledLeaseReleasesItsRequestAndCallbackReferences(): void
    {
        [$engine, $registry] = $this->policy();
        $driver              = new TransferDriver();
        $request             = new Request('GET', 'https://api.example');
        $requestReference    = WeakReference::create($request);
        $captured            = new stdClass();
        $capturedReference   = WeakReference::create($captured);
        $callback            = static function () use ($captured): void {
            $captured->called = true;
        };
        $lease = new TransferLease($driver, $engine, $registry->newContext(), $request, ['on_stats' => $callback]);
        unset($request, $callback, $captured);
        self::assertNotNull($requestReference->get());
        self::assertNotNull($capturedReference->get());
        $lease->promise()->cancel();
        self::assertNull($requestReference->get());
        self::assertNull($capturedReference->get());
        self::assertSame(0, $driver->counters()['active']);
    }

    /** @return array{PolicyEngine,PolicyRegistry} */
    private function policy(): array
    {
        $clock    = new SystemClock();
        $registry = new PolicyRegistry(GuardConfig::fromArray([]), $clock);
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->expects(self::never())->method('resolve');

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
}
