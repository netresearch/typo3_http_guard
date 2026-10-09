<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Transport;

use Closure;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use LogicException;
use Netresearch\HttpGuard\ConnectionPlan;
use Netresearch\HttpGuard\NativeOperation;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\RequestPolicyContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/** A lazy, single-attempt owner. No multi handle is shared with another lease. */
final class TransferLease
{
    private ?CurlMultiHandler $handler = null;
    private ?PromiseInterface $inner   = null;
    private PromiseInterface $outer;
    private ?ConnectionPlan $plan = null;
    private bool $done            = false;
    private bool $executing       = false;
    private bool $releasePending  = false;
    private bool $cancelled       = false;
    private bool $startedNetwork  = false;

    /** @param array<string,mixed> $options */
    public function __construct(
        private readonly TransferDriver $driver,
        private readonly PolicyEngine $engine,
        private readonly RequestPolicyContext $context,
        private ?RequestInterface $request,
        private array $options,
    ) {
        $this->outer = new Promise(
            fn () => $this->driver->waitFor($this),
            function (): void {
                $this->cancelled = true;
                $this->inner?->cancel();
                $this->release();
            },
        );
        $this->driver->register($this);
    }

    public function promise(): PromiseInterface
    {
        return $this->outer;
    }

    public function settled(): bool
    {
        return $this->done;
    }

    public function tick(): void
    {
        if ($this->done) {
            return;
        }
        $this->executing = true;
        try {
            if ($this->inner === null) {
                $this->prepare();
            }
            if ($this->stopCancelled() || $this->releasePending) {
                return;
            }
            if (!$this->startedNetwork) {

                if ($this->plan === null) {
                    throw new PolicyException('transport_unsupported');
                }
                $this->engine->assertCurrent($this->plan, $this->context);

                if ($this->stopCancelled()) {
                    return;
                }

                if (OptionSanitizer::processProxyNames() !== []) {
                    throw new PolicyException('proxy_unsupported');
                }
                $this->startedNetwork = true;

            }
            $this->handler?->tick();
        } catch (Throwable $error) {

            if ($error instanceof PolicyException && !$this->policyFailureReported) {
                $this->engine->reportDiagnostic($this->context, 'deny', $error->reasonCode());
                $this->policyFailureReported = true;
            }
            $this->inner?->cancel();

            if ($this->outer->getState() === PromiseInterface::PENDING) {
                $this->outer->reject($error);
            }
            $this->release();
        } finally {
            $this->executing = false;
            if ($this->releasePending) {
                $this->releaseNow();
            }
        }
    }

    private function prepare(): void
    {
        RuntimeSupport::assertSupported();
        $request = $this->request;
        if ($request === null || $this->stopCancelled()) {
            $this->release();

            return;
        }
        $plan       = $this->planRequest($request);
        $this->plan = $plan;
        $this->engine->assertCurrent($plan, $this->context);
        if ($this->stopCancelled()) {
            return;
        }
        $this->request = $plan->target->canonicalRequest;
        $handler       = new CurlMultiHandler(
            [
                'handle_factory'    => new SingleUseCurlFactory(new CurlFactory(0)),
                'transport_sharing' => 'none',
                'select_timeout'    => 0.001,
            ],
        );
        $this->handler = $handler;
        $this->driver->nativeConstructed();
        $leaf                           = $this->options;
        $raw                            = $leaf['curl'] ?? [];
        $raw[CURLOPT_FRESH_CONNECT]     = true;
        $raw[CURLOPT_FORBID_REUSE]      = true;
        $raw[CURLOPT_DNS_CACHE_TIMEOUT] = 0;
        if ($plan->target->literalIp === null) {
            $raw[CURLOPT_RESOLVE] = [self::pin($plan)];
        }
        $leaf['curl']       = $raw;
        $leaf['proxy']      = '';
        $userHeaders        = $leaf['on_headers'] ?? null;
        $leaf['on_headers'] = static function (...$arguments) use ($userHeaders): void {
            if ($userHeaders !== null) {
                $userHeaders(...$arguments);
            }
        };
        $userStats        = $leaf['on_stats'] ?? null;
        $allowed          = $plan->addresses;
        $leaf['on_stats'] = static function (TransferStats $stats) use ($userStats, $allowed): void {
            $primary = $stats->getHandlerStat('primary_ip');
            if ($stats->hasResponse() && (!is_string($primary) || !self::sameAddressIn($primary, $allowed))) {
                throw new PolicyException('transport_unsupported');
            }
            if ($userStats !== null) {
                $userStats($stats);
            }
        };
        $inner       = $handler($plan->target->canonicalRequest, $leaf);
        $this->inner = $inner;
        // Body preparation may invoke caller callbacks and expire or cancel the grant.
        $this->engine->assertCurrent($plan, $this->context);
        if ($this->stopCancelled()) {
            $inner->cancel();

            return;
        }
        $inner->then(
            function (ResponseInterface $response): void {
                if ($this->outer->getState() === PromiseInterface::PENDING) {
                    $this->outer->resolve($response);
                }
                $this->release();
            },
            function ($reason): void {
                if ($this->outer->getState() === PromiseInterface::PENDING) {
                    $this->outer->reject($reason);
                }
                $this->release();
            },
        );
    }

    private function release(): void
    {
        if ($this->done) {
            return;
        }
        if ($this->executing) {
            $this->releasePending = true;

            return;
        }
        $this->releaseNow();
    }

    private function releaseNow(): void
    {
        if ($this->done) {
            return;
        }
        $this->done = true;
        if ($this->handler !== null) {

            $method = RuntimeSupport::major() === 8 ? 'close' : '__destruct';

            self::nativeCleanupCallback($this->handler, $method)();


        }
        $this->handler = null;
        $this->inner   = null;
        $this->plan    = null;
        $this->request = null;
        $this->options = [];
        $this->driver->release($this);
    }

    /** @return Closure(): void */
    private static function nativeCleanupCallback(CurlMultiHandler $handler, string $method): Closure
    {
        $callback = [$handler, $method];
        if (!is_callable($callback)) {
            throw new LogicException('Unsupported native cleanup');
        }

        return Closure::fromCallable($callback);
    }

    private static function pin(ConnectionPlan $plan): string
    {
        if ($plan->addresses === []) {
            throw new PolicyException('resolution_unverified');
        }
        $addresses = [];
        foreach ($plan->addresses as $ip) {
            if (NativeOperation::attempt(static fn () => inet_pton($ip)) === false) {
                throw new PolicyException('resolution_unverified');
            }
            $addresses[] = str_contains($ip, ':') ? '[' . $ip . ']' : $ip;
        }

        return $plan->target->host . ':' . $plan->target->port . ':' . implode(',', $addresses);
    }

    /** @param list<string> $addresses */
    private static function sameAddressIn(string $ip, array $addresses): bool
    {
        $binary = NativeOperation::attempt(static fn () => inet_pton($ip));
        if ($binary === false) {
            return false;
        }
        if (strlen($binary) === 16 && substr($binary, 0, 12) === str_repeat("\x00", 10) . "\xff\xff") {
            $binary = substr($binary, 12);
        }
        foreach ($addresses as $allowed) {
            $candidate = NativeOperation::attempt(static fn () => inet_pton($allowed));
            if ($candidate !== false && strlen($candidate) === 16 && substr($candidate, 0, 12) === str_repeat("\x00", 10) . "\xff\xff") {
                $candidate = substr($candidate, 12);
            }
            if ($candidate === $binary) {
                return true;
            }
        }

        return false;
    }

    /** @phpstan-impure Stops and releases a cancelled transfer. */
    private function stopCancelled(): bool
    {
        if ($this->cancelled) {
            $this->release();

            return true;
        }

        return false;
    }
    private bool $policyFailureReported = false;

    private function planRequest(RequestInterface $request): ConnectionPlan
    {
        try {
            return $this->engine->plan($request, $this->context);
        } catch (PolicyException $error) {
            $this->policyFailureReported = true;
            throw $error;
        }
    }
}
