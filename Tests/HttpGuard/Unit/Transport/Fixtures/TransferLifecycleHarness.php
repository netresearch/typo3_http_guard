<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures;

use Closure;
use DateTimeImmutable;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Netresearch\HttpGuard\{AddressClassifier, ClockInterface, DecisionEvent, DecisionReporterInterface, GuardConfig, PolicyEngine, PolicyRegistry, Resolution, ResolverInterface, TargetNormalizer};
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use Throwable;

/** Fixed ABI double for the native leaf only. Promises, graph metadata and policy stay real. */
final class OfflineCurlMultiHandler
{
    /** @var list<self> */
    public static array $instances             = [];
    public static ?Closure $duringInvoke       = null;
    public static ?Closure $duringTick         = null;
    public static ?Throwable $constructorError = null;
    public static ?Throwable $tickError        = null;
    public static bool $autoResolve            = true;
    public static bool $reject                 = false;
    public static mixed $reason                = null;
    public static mixed $primaryIp             = '8.8.8.8';
    public static bool $statsHaveResponse      = true;
    public static bool $emitHeaders            = true;
    public static bool $emitStats              = true;

    public array $leaf                = [];
    public ?RequestInterface $request = null;
    public ?Promise $inner            = null;
    public ?TransferStats $stats      = null;
    public ResponseInterface $response;
    public int $ticks             = 0;
    public int $cancellations     = 0;
    public int $closes            = 0;
    public int $destructors       = 0;
    public bool $insideTick       = false;
    public array $closeInsideTick = [];

    public function __construct(public array $constructorOptions = [])
    {
        if (self::$constructorError instanceof Throwable) {
            throw self::$constructorError;
        }
        $this->response    = new Response(200, [], 'offline lifecycle response');
        self::$instances[] = $this;
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $this->request = $request;
        $this->leaf    = $options;
        $this->inner   = new Promise(
            null,
            function (): void {
                ++$this->cancellations;
            },
        );
        if (self::$duringInvoke instanceof Closure) {
            (self::$duringInvoke)($this);
        }

        return $this->inner;
    }

    public function tick(): void
    {
        ++$this->ticks;
        $this->insideTick = true;
        try {
            if (self::$duringTick instanceof Closure) {
                (self::$duringTick)($this);
            }
            if (self::$tickError instanceof Throwable) {
                throw self::$tickError;
            }
            if (!self::$autoResolve || $this->inner?->getState() !== PromiseInterface::PENDING) {
                return;
            }
            if (self::$emitHeaders) {
                $this->leaf['on_headers']($this->response, 'native-header-context');
            }
            $this->stats = new TransferStats(
                $this->request,
                self::$statsHaveResponse ? $this->response : null,
                0.25,
                null,
                ['primary_ip' => self::$primaryIp],
            );
            if (self::$emitStats) {
                $this->leaf['on_stats']($this->stats);
            }
            if ($this->inner->getState() === PromiseInterface::PENDING) {
                if (self::$reject) {
                    $this->inner->reject(self::$reason);
                } else {
                    $this->inner->resolve($this->response);
                }
            }
        } finally {
            $this->insideTick = false;
        }
    }

    public function close(): void
    {
        ++$this->closes;
        $this->closeInsideTick[] = $this->insideTick;
    }

    public function __destruct()
    {
        ++$this->destructors;
        $this->destructorInsideTick[] = $this->insideTick;
    }
    public array $destructorInsideTick = [];
}

final class LifecycleClock implements ClockInterface
{
    public int $seconds = 0;

    public function now(): DateTimeImmutable
    {
        return (new DateTimeImmutable('2026-10-10T00:00:00Z'))->modify('+' . $this->seconds . ' seconds');
    }

    public function monotonic(): float
    {
        return $this->seconds;
    }
}
final class LifecycleResolver implements ResolverInterface
{
    public array $addresses        = ['8.8.8.8'];
    public array $calls            = [];
    public ?Closure $duringResolve = null;

    public function resolve(string $host): Resolution
    {
        $this->calls[] = $host;
        if ($this->duringResolve instanceof Closure) {
            ($this->duringResolve)();
        }

        return new Resolution($this->addresses, 'offline-lifecycle-fixture', 5, 'fixed-lifecycle-generation');
    }
}
final class LifecycleReporter implements DecisionReporterInterface
{
    public array $events          = [];
    public ?Closure $duringReport = null;

    public function report(DecisionEvent $event): void
    {
        $this->events[] = $event;
        if ($this->duringReport instanceof Closure) {
            ($this->duringReport)($event);
        }
    }
}
final readonly class LifecyclePolicy
{
    public LifecycleClock $clock;
    public LifecycleResolver $resolver;
    public LifecycleReporter $reporter;
    public GuardConfig $config;
    public PolicyRegistry $registry;
    public PolicyEngine $engine;

    public function __construct(?string $redirects = null, bool $expiring = false)
    {
        $this->clock    = new LifecycleClock();
        $this->resolver = new LifecycleResolver();
        $this->reporter = new LifecycleReporter();
        $data           = [];
        if ($redirects !== null || $expiring) {
            $data['endpoints']['fixture'] = [
                'origin'       => 'https://public.example',
                'allowedCidrs' => ['8.8.8.8/32'],
                'methods'      => ['GET'],
                'purpose'      => 'Offline lifecycle contract',
                'owner'        => 'tests',
                'redirects'    => $redirects ?? 'same-origin',
                'expiresAt'    => $expiring ? '2026-10-10T00:00:01Z' : null,
            ];
        }
        $this->config   = GuardConfig::fromArray($data);
        $this->registry = new PolicyRegistry($this->config, $this->clock);
        $this->engine   = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $this->resolver,
            $this->registry,
            $this->clock,
            $this->reporter,
        );
    }
}
