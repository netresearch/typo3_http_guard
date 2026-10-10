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
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Netresearch\HttpGuard\{DecisionEvent, PolicyException};
use Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures\{LifecyclePolicy, OfflineCurlMultiHandler};
use Netresearch\HttpGuard\Transport\{RuntimeSupport, SingleUseCurlFactory, TransferDriver, TransferLease};
use PHPUnit\Framework\Attributes\{DataProvider, PreserveGlobalState, RunTestsInSeparateProcesses};
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class TransferLeaseLifecycleContractTest extends TestCase
{
    private array $proxies = [];

    protected function setUp(): void
    {
        require_once __DIR__ . '/Fixtures/TransferLifecycleHarness.php';
        self::assertFalse(class_exists(CurlMultiHandler::class, false), 'Only the unloaded native leaf may be doubled');
        self::assertTrue(class_alias(OfflineCurlMultiHandler::class, CurlMultiHandler::class));
        RuntimeSupport::assertSupported();
        foreach (['http_proxy', 'https_proxy', 'all_proxy', 'HTTP_PROXY', 'HTTPS_PROXY', 'ALL_PROXY', 'no_proxy', 'NO_PROXY'] as $name) {
            $this->proxies[$name] = getenv($name);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        Utils::queue()->run();
        foreach ($this->proxies as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }
    }

    private function lease(
        LifecyclePolicy $policy,
        array $options = [],
        string $uri = 'https://PUBLIC.EXAMPLE.:443/path',
    ): array {
        $driver  = new TransferDriver();
        $context = $policy->registry->newContext(isset($policy->config->data['endpoints']['fixture']) ? 'fixture' : null);

        return [new TransferLease($driver, $policy->engine, $context, new Request('GET', $uri), $options), $driver];
    }

    private function assertReleased(TransferLease $lease, TransferDriver $driver, int $native = 1): void
    {
        self::assertTrue($lease->settled());
        self::assertSame(
            ['created' => 1, 'released' => 1, 'active' => 0, 'peak' => 1, 'nativeConstructed' => $native],
            $driver->counters(),
        );
        $lease->tick();
        $driver->tick();
        $driver->waitFor($lease);
        self::assertSame(1, $driver->counters()['released']);
        foreach (OfflineCurlMultiHandler::$instances as $handler) {
            self::assertSame(RuntimeSupport::major() === 8 ? 1 : 0, $handler->closes);
            self::assertSame(RuntimeSupport::major() === 8 ? 0 : 1, $handler->destructors);
            self::assertNotContains(
                true,
                array_merge($handler->closeInsideTick, $handler->destructorInsideTick),
                'Cleanup must wait until the native callback frame exits',
            );
        }
    }

    private function rejectReason(TransferLease $lease): mixed
    {
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

        return $reason;
    }

    #[DataProvider('dnsPins')]
    public function testFirstAttemptReceivesCanonicalRequestAndExactSingleUseOptions(
        string $uri,
        array $addresses,
        ?string $pin,
    ): void {
        $policy                      = new LifecyclePolicy();
        $policy->resolver->addresses = $addresses;
        $headers                     = [];
        $stats                       = [];
        [$lease, $driver]            = $this->lease(
            $policy,
            [
                'curl' => [
                    CURLOPT_CONNECTTIMEOUT_MS => 123,
                    CURLOPT_FRESH_CONNECT     => false,
                    CURLOPT_FORBID_REUSE      => false,
                    CURLOPT_DNS_CACHE_TIMEOUT => 99,
                ],
                'proxy'      => 'ignored input',
                'on_headers' => static function (...$args) use (&$headers): void {
                    $headers = $args;
                },
                'on_stats' => static function (TransferStats $value) use (&$stats): void {
                    $stats[] = $value;
                },
            ],
            $uri,
        );
        OfflineCurlMultiHandler::$primaryIp = $addresses[0];
        self::assertSame([], OfflineCurlMultiHandler::$instances);
        $response = $this->complete($lease, $driver);
        $handler  = OfflineCurlMultiHandler::$instances[0];
        self::assertSame($handler->response, $response);
        self::assertSame(1, $handler->ticks);
        self::assertSame('none', $handler->constructorOptions['transport_sharing']);
        self::assertSame(0.001, $handler->constructorOptions['select_timeout']);
        self::assertInstanceOf(SingleUseCurlFactory::class, $handler->constructorOptions['handle_factory']);
        self::assertTrue($handler->leaf['curl'][CURLOPT_FRESH_CONNECT]);
        self::assertTrue($handler->leaf['curl'][CURLOPT_FORBID_REUSE]);
        self::assertSame(0, $handler->leaf['curl'][CURLOPT_DNS_CACHE_TIMEOUT]);
        self::assertSame(123, $handler->leaf['curl'][CURLOPT_CONNECTTIMEOUT_MS]);
        self::assertSame('', $handler->leaf['proxy']);
        if ($pin === null) {
            self::assertArrayNotHasKey(CURLOPT_RESOLVE, $handler->leaf['curl']);
            self::assertSame([], $policy->resolver->calls);
        } else {
            self::assertSame([$pin], $handler->leaf['curl'][CURLOPT_RESOLVE]);
            self::assertSame(['public.example'], $policy->resolver->calls);
        }
        self::assertSame(
            $uri === 'https://PUBLIC.EXAMPLE.:443/path' ? 'https://public.example/path' : $uri,
            (string) $handler->request->getUri(),
        );
        self::assertSame([$response, 'native-header-context'], $headers);
        self::assertSame([$handler->stats], $stats);
        self::assertSame(0.25, $stats[0]->getTransferTime());
        self::assertSame(0, $handler->cancellations);
        $this->assertReleased($lease, $driver);
    }

    public static function dnsPins(): iterable
    {
        yield 'v4 and canonical authority' => ['https://PUBLIC.EXAMPLE.:443/path', ['8.8.8.8'], 'public.example:443:8.8.8.8'];
        yield 'v6 pin brackets' => ['https://public.example/path', ['2606:4700:4700::1111'], 'public.example:443:[2606:4700:4700::1111]'];
        yield 'multiple addresses preserve order' => [
            'https://public.example/path',
            ['8.8.8.8', '2606:4700:4700::1111', '1.1.1.1'],
            'public.example:443:8.8.8.8,[2606:4700:4700::1111],1.1.1.1',
        ];
        yield 'custom port' => ['https://public.example:8443/path', ['8.8.8.8'], 'public.example:8443:8.8.8.8'];
        yield 'literal v4 avoids DNS pin' => ['https://8.8.8.8/path', ['8.8.8.8'], null];
        yield 'literal v6 avoids DNS pin' => ['https://[2606:4700:4700::1111]/path', ['2606:4700:4700::1111'], null];
    }

    #[DataProvider('primaryAddresses')]
    public function testStatsValidateConnectedAddressBeforeForwardingCallerCallback(
        array $allowed,
        mixed $primary,
        bool $hasResponse,
        bool $accepted,
    ): void {
        if (!$hasResponse) {
            OfflineCurlMultiHandler::$reject = true;
            OfflineCurlMultiHandler::$reason = new RuntimeException('ordinary no-response transfer failure');
        }
        $policy                                     = new LifecyclePolicy();
        $policy->resolver->addresses                = $allowed;
        $calls                                      = 0;
        OfflineCurlMultiHandler::$primaryIp         = $primary;
        OfflineCurlMultiHandler::$statsHaveResponse = $hasResponse;
        [$lease, $driver]                           = $this->lease(
            $policy,
            [
                'on_stats' => static function (TransferStats $stats) use (&$calls): void {
                    ++$calls;
                },
            ],
        );
        $driver->tick();
        if ($accepted) {
            if ($hasResponse) {
                self::assertInstanceOf(Response::class, $lease->promise()->wait());
            } else {
                self::assertSame(OfflineCurlMultiHandler::$reason, $this->rejectReason($lease));
                self::assertSame(0, OfflineCurlMultiHandler::$instances[0]->cancellations);
            }
            self::assertSame(1, $calls);
        } else {
            $error = $this->rejectReason($lease);
            self::assertInstanceOf(PolicyException::class, $error);
            self::assertSame('transport_unsupported', $error->reasonCode());
            self::assertSame(0, $calls);
            self::assertSame(1, OfflineCurlMultiHandler::$instances[0]->cancellations);
        }
        $this->assertReleased($lease, $driver);
    }

    public static function primaryAddresses(): iterable
    {
        yield 'exact v4' => [['8.8.8.8'], '8.8.8.8', true, true];
        yield 'second allowed address' => [['1.1.1.1', '8.8.8.8'], '8.8.8.8', true, true];
        yield 'mapped v4 connected address' => [['8.8.8.8'], '::ffff:8.8.8.8', true, true];
        yield 'canonicalized mapped grant' => [['::ffff:8.8.8.8'], '8.8.8.8', true, true];
        yield 'expanded v6' => [['2606:4700:4700::1111'], '2606:4700:4700:0000:0000:0000:0000:1111', true, true];
        yield 'different v4' => [['8.8.8.8'], '1.1.1.1', true, false];
        yield 'same suffix different v6' => [['2606:4700:4700::1111'], '2606:4700:4701::1111', true, false];
        yield 'invalid address text' => [['8.8.8.8'], 'invalid', true, false];
        yield 'absent address on response' => [['8.8.8.8'], null, true, false];
        yield 'numeric address on response' => [['8.8.8.8'], 42, true, false];
        yield 'no response stats permit missing primary' => [['8.8.8.8'], null, false, true];
    }

    public function testPendingAttemptIsConstructedOnceAcrossTicksAndCancellationReachesInnerOnce(): void
    {
        OfflineCurlMultiHandler::$autoResolve = false;
        [$lease, $driver]                     = $this->lease(new LifecyclePolicy());
        $driver->tick();
        $driver->tick();
        self::assertFalse($lease->settled());
        self::assertSame(PromiseInterface::PENDING, $lease->promise()->getState());
        self::assertCount(1, OfflineCurlMultiHandler::$instances);
        $handler = OfflineCurlMultiHandler::$instances[0];
        self::assertSame(2, $handler->ticks);
        $lease->promise()->cancel();
        $lease->promise()->cancel();
        self::assertSame(1, $handler->cancellations);
        self::assertSame(PromiseInterface::REJECTED, $handler->inner->getState());
        $this->assertReleased($lease, $driver);
    }

    public function testCancellationDuringBodyPreparationNeverStartsNativeTick(): void
    {
        [$lease, $driver]                      = $this->lease(new LifecyclePolicy());
        OfflineCurlMultiHandler::$duringInvoke = static function () use ($lease): void {
            $lease->promise()->cancel();
            self::assertFalse($lease->settled(), 'Native frame still owns cleanup');
        };
        $driver->tick();
        $handler = OfflineCurlMultiHandler::$instances[0];
        self::assertSame(0, $handler->ticks);
        self::assertSame(1, $handler->cancellations);
        self::assertSame(PromiseInterface::REJECTED, $lease->promise()->getState());
        $this->assertReleased($lease, $driver);
    }

    public function testCancellationInsideNativeCallbackDefersCleanupUntilTickReturns(): void
    {
        [$lease, $driver] = $this->lease(
            new LifecyclePolicy(),
            [
                'on_headers' => static function () use (&$lease): void {
                    $lease->promise()->cancel();
                    self::assertFalse($lease->settled());
                    self::assertSame(0, OfflineCurlMultiHandler::$instances[0]->closes);
                },
            ],
        );
        $driver->tick();
        self::assertSame(1, OfflineCurlMultiHandler::$instances[0]->cancellations);
        $this->assertReleased($lease, $driver);
    }

    #[DataProvider('preparationChanges')]
    public function testCallerPreparationCannotIntroduceProxyOrExpireEndpointBeforeFirstTick(
        string $change,
        string $reason,
    ): void {
        $policy                                = new LifecyclePolicy(null, true);
        [$lease, $driver]                      = $this->lease($policy);
        OfflineCurlMultiHandler::$duringInvoke = static function () use ($policy, $change): void {
            if ($change === 'expire') {
                $policy->clock->seconds = 1;
            } else {
                putenv('https_proxy=http://synthetic.invalid:9');
            }
        };
        $driver->tick();
        $error = $this->rejectReason($lease);
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame($reason, $error->reasonCode());
        $handler = OfflineCurlMultiHandler::$instances[0];
        self::assertSame(0, $handler->ticks);
        self::assertSame(1, $handler->cancellations);
        self::assertSame(['allow', 'deny'], array_column($policy->reporter->events, 'decision'));
        $this->assertReleased($lease, $driver);
    }

    public static function preparationChanges(): iterable
    {
        yield 'grant expiry' => ['expire', 'grant_invalid'];
        yield 'introduced process proxy' => ['proxy', 'proxy_unsupported'];
    }

    public function testReentrantAllowReporterCancellationDoesNotConstructNativeHandler(): void
    {
        $policy                         = new LifecyclePolicy();
        [$lease, $driver]               = $this->lease($policy);
        $policy->reporter->duringReport = static function (DecisionEvent $event) use ($lease): void {
            if ($event->decision === 'allow') {
                $lease->promise()->cancel();
            }
        };
        $driver->tick();
        self::assertSame([], OfflineCurlMultiHandler::$instances);
        self::assertSame(PromiseInterface::REJECTED, $lease->promise()->getState());
        $this->assertReleased($lease, $driver, 0);
    }

    public function testPolicyDenialIsReportedOnceBeforeNativeConstruction(): void
    {
        $policy                      = new LifecyclePolicy();
        $policy->resolver->addresses = ['10.1.2.3'];
        [$lease, $driver]            = $this->lease($policy);
        $driver->tick();
        $error = $this->rejectReason($lease);
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame('address_forbidden', $error->reasonCode());
        self::assertSame(['deny'], array_column($policy->reporter->events, 'decision'));
        self::assertSame([], OfflineCurlMultiHandler::$instances);
        $this->assertReleased($lease, $driver, 0);
    }

    #[DataProvider('nativeFailures')]
    public function testNativeAndUserCallbackFailuresKeepExactReasonAndReleaseOnce(
        string $stage,
        bool $policyError,
    ): void {
        $policy  = new LifecyclePolicy();
        $error   = $policyError ? new PolicyException('option_forbidden') : new RuntimeException('ordinary offline callback failure');
        $options = [];
        if ($stage === 'constructor') {
            OfflineCurlMultiHandler::$constructorError = $error;
        } elseif ($stage === 'tick') {
            OfflineCurlMultiHandler::$tickError = $error;
        } elseif ($stage === 'inner') {
            OfflineCurlMultiHandler::$reject = true;
            OfflineCurlMultiHandler::$reason = $error;
        } else {
            $options[$stage] = static function () use ($error): never {
                throw $error;
            };
        }
        [$lease, $driver] = $this->lease($policy, $options);
        $driver->tick();
        self::assertSame($error, $this->rejectReason($lease));
        $expectedDenies = $policyError && $stage !== 'inner' ? 1 : 0;
        self::assertCount(
            $expectedDenies,
            array_filter(
                $policy->reporter->events,
                static fn (DecisionEvent $event): bool => $event->decision === 'deny',
            ),
        );
        if ($stage !== 'constructor') {
            self::assertSame($stage === 'inner' ? 0 : 1, OfflineCurlMultiHandler::$instances[0]->cancellations);
        }
        $this->assertReleased($lease, $driver, $stage === 'constructor' ? 0 : 1);
    }

    public static function nativeFailures(): iterable
    {
        foreach (['constructor', 'tick', 'inner', 'on_headers', 'on_stats'] as $stage) {
            yield $stage . ' ordinary' => [$stage, false];
            yield $stage . ' policy' => [$stage, true];
        }
    }

    #[DataProvider('outerSettlements')]
    public function testExternallySettledOuterPromiseDoesNotChangeOnLaterInnerCompletion(
        bool $outerReject,
        bool $innerReject,
    ): void {
        OfflineCurlMultiHandler::$autoResolve = false;
        [$lease, $driver]                     = $this->lease(new LifecyclePolicy());
        $driver->tick();
        $handler  = OfflineCurlMultiHandler::$instances[0];
        $external = $outerReject ? new RuntimeException('outer reason') : new Response(202);
        if ($outerReject) {
            $lease->promise()->reject($external);
        } else {
            $lease->promise()->resolve($external);
        }
        if ($innerReject) {
            $handler->inner->reject(new RuntimeException('later inner reason'));
        } else {
            $handler->inner->resolve(new Response(204));
        }
        Utils::queue()->run();
        if ($outerReject) {
            self::assertSame($external, $this->rejectReason($lease));
        } else {
            self::assertSame($external, $lease->promise()->wait());
        }
        $this->assertReleased($lease, $driver);
    }

    public static function outerSettlements(): iterable
    {
        foreach ([false, true] as $outer) {
            foreach ([false, true] as $inner) {
                yield (int) $outer . '-' . (int) $inner => [$outer, $inner];
            }
        }
    }

    public function testProcessProxyIntroducedAfterFirstTickCannotReconfigureStartedAttempt(): void
    {
        $policy                               = new LifecyclePolicy();
        OfflineCurlMultiHandler::$autoResolve = false;
        [$lease, $driver]                     = $this->lease($policy);
        $driver->tick();
        self::assertFalse($lease->settled());
        $handler = OfflineCurlMultiHandler::$instances[0];
        self::assertSame(1, $handler->ticks);
        putenv('https_proxy=http://synthetic.invalid:9');
        OfflineCurlMultiHandler::$autoResolve = true;
        $driver->tick();
        self::assertSame(PromiseInterface::FULFILLED, $lease->promise()->getState());
        self::assertSame($handler->response, $lease->promise()->wait());
        self::assertSame('', $handler->leaf['proxy']);
        self::assertSame(2, $handler->ticks);
        self::assertCount(1, OfflineCurlMultiHandler::$instances);
        $this->assertReleased($lease, $driver);
    }

    public function testMissingRequestReleasesWithoutNativeConstructionOrUnresolvedPromise(): void
    {
        $policy = new LifecyclePolicy();
        $driver = new TransferDriver();
        $lease  = new TransferLease($driver, $policy->engine, $policy->registry->newContext(), null, []);
        $driver->tick();
        $error = $this->rejectReason($lease);
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame('transport_unsupported', $error->reasonCode());
        self::assertSame([], OfflineCurlMultiHandler::$instances);
        $this->assertReleased($lease, $driver, 0);
        self::assertNull($lease->promise()->wait(false));
        self::assertSame([], $policy->resolver->calls);
        self::assertSame(['transport_unsupported'], array_column($policy->reporter->events, 'reasonCode'));
    }

    public function testOrdinaryLeafOptionsSurviveNativeOptionNormalization(): void
    {
        [$lease, $driver] = $this->lease(
            new LifecyclePolicy(),
            [
                'timeout'         => 1.25,
                'connect_timeout' => 0.75,
                'headers'         => ['X-Offline-Contract' => 'ordinary'],
                'on_headers'      => null,
                'on_stats'        => null,
                'curl'            => null,
            ],
        );
        $driver->tick();
        self::assertSame(PromiseInterface::FULFILLED, $lease->promise()->getState());
        $leaf = OfflineCurlMultiHandler::$instances[0]->leaf;
        self::assertSame(1.25, $leaf['timeout']);
        self::assertSame(0.75, $leaf['connect_timeout']);
        self::assertSame(['X-Offline-Contract' => 'ordinary'], $leaf['headers']);
        $this->assertReleased($lease, $driver);
    }

    private function complete(TransferLease $lease, TransferDriver $driver): mixed
    {
        $driver->tick();
        self::assertSame(
            PromiseInterface::FULFILLED,
            $lease->promise()->getState(),
            'One tick must publish the completed response',
        );

        return $lease->promise()->wait();
    }
}
