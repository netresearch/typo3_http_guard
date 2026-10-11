<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures as Shapes;
use Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures\LifecyclePolicy;
use Netresearch\HttpGuard\Transport\{TransferDriver, TransferLease};
use PHPUnit\Framework\Attributes\{DataProvider, PreserveGlobalState, RunTestsInSeparateProcesses};
use PHPUnit\Framework\TestCase;

/** Deliberately invalid leaf ABIs test the public lease preflight; installed SDK metadata stays real. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class TransferLeaseRuntimeBoundaryContractTest extends TestCase
{
    #[DataProvider('unsupportedNativeShapes')]
    public function testUnsupportedNativeLeafFailsBeforeDnsAndNativeConstruction(string $shape): void
    {
        require_once __DIR__ . '/Fixtures/TransferLifecycleHarness.php';
        require_once __DIR__ . '/Fixtures/RuntimeContractShapes.php';
        self::assertFalse(class_exists(CurlMultiHandler::class, false));
        self::assertTrue(class_alias($shape, CurlMultiHandler::class));
        $policy = new LifecyclePolicy();
        $driver = new TransferDriver();
        $lease  = new TransferLease(
            $driver,
            $policy->engine,
            $policy->registry->newContext(),
            new Request('GET', 'https://public.example'),
            [],
        );
        $driver->tick();
        $reason = null;
        $lease
            ->promise()
            ->then(
                null,
                static function (mixed $error) use (&$reason): void {
                    $reason = $error;
                },
            );
        Utils::queue()->run();
        self::assertSame(PromiseInterface::REJECTED, $lease->promise()->getState());
        self::assertInstanceOf(PolicyException::class, $reason);
        self::assertSame('transport_unsupported', $reason->reasonCode());
        self::assertNull($lease->promise()->wait(false));
        self::assertSame([], $policy->resolver->calls);
        self::assertSame(['transport_unsupported'], array_column($policy->reporter->events, 'reasonCode'));
        self::assertTrue($lease->settled());
        self::assertSame(
            ['created' => 1, 'released' => 1, 'active' => 0, 'peak' => 1, 'nativeConstructed' => 0],
            $driver->counters(),
        );
        $driver->tick();
        $lease->tick();
        self::assertSame(1, $driver->counters()['released']);
    }

    public static function unsupportedNativeShapes(): iterable
    {
        foreach ([
            Shapes\MultiContractMissingArgument::class,
            Shapes\MultiContractMandatoryExtra::class,
            Shapes\MultiContractPrivateConstructor::class,
            Shapes\MultiContractShortInvocation::class,
            Shapes\MultiContractPrivateTick::class,
            Shapes\MultiContractStaticTick::class,
            Shapes\MultiContractMandatoryTick::class,
            Shapes\MultiContractMissingCleanup::class,
        ] as $shape) {
            yield $shape => [$shape];
        }
    }
}
