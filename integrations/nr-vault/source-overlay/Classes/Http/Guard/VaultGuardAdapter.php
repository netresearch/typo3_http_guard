<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);

namespace Netresearch\NrVault\Http\Guard;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\RequestPolicyContext;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\NrVault\Http\CancellableTransport;
use Netresearch\NrVault\Http\SecureHttpClientFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Explicit resource binding. Only enforce replaces the legacy transport.
 *
 * @internal
 *
 * @phpstan-import-type GuardMiddleware from SecureHttpClientFactory
 * @phpstan-import-type GuardHandler from SecureHttpClientFactory
 */
final readonly class VaultGuardAdapter implements VaultGuardAdapterInterface
{
    private RequestPolicyContext $diagnosticContext;

    private SecureHttpClientFactory $legacyFactory;

    public function __construct(
        private GuardedClientFactory $clientFactory,
        private PolicyEngine $engine,
        private ?string $resourceEndpointId = null,
        private ?SecureHttpClientFactory $oauthTokenFactory = null,
        ?SecureHttpClientFactory $legacyFactory = null,
    ) {
        $this->diagnosticContext = $clientFactory->middlewarePair($resourceEndpointId)->context;
        $this->legacyFactory = $legacyFactory ?? new SecureHttpClientFactory();
        if ($this->legacyFactory->isGuardEnabled()) {
            throw new PolicyException('configuration_invalid');
        }
    }

    public function mode(): string
    {
        return $this->diagnosticContext->mode;
    }

    public function create(array $platformOptions): ClientInterface
    {
        if ($this->diagnosticContext->mode === 'enforce') {
            return $this->clientFactory->createTransport(
                $platformOptions,
                $this->resourceEndpointId,
            )->client;
        }

        $legacyClient = $this->legacyFactory->create();
        if (!$legacyClient instanceof GuzzleClientInterface) {
            throw new PolicyException('configuration_invalid');
        }

        return $this->legacyFactory->withDiagnosticOptions(
            $legacyClient,
            $platformOptions,
            $this->observationMiddleware(),
        );
    }

    public function createCancellable(
        array $platformOptions,
        float $wallClockBudgetSeconds,
        ?float $idleBudgetSeconds,
    ): ?CancellableTransport {
        if ($this->diagnosticContext->mode === 'enforce') {
            $binding = $this->clientFactory->createTransport(
                $platformOptions,
                $this->resourceEndpointId,
            );

            return new CancellableTransport(
                $binding->client,
                new GuardTransferTicker($binding->driver),
                $wallClockBudgetSeconds,
                $idleBudgetSeconds,
            );
        }

        $legacy = $this->legacyFactory->createCancellable();
        if (!$legacy instanceof CancellableTransport) {
            return null;
        }

        return new CancellableTransport(
            $this->legacyFactory->withDiagnosticOptions(
                $legacy->client(),
                $platformOptions,
                $this->observationMiddleware(),
            ),
            $legacy->ticker(),
            $wallClockBudgetSeconds,
            $idleBudgetSeconds,
        );
    }

    public function isHostAllowed(string $host): bool
    {
        return $this->diagnosticContext->mode === 'enforce' ? $this->engine->hostAllowed($host, $this->diagnosticContext) : $this->legacyFactory->isHostAllowed($host);
    }

    public function assertRequestAllowed(RequestInterface $request): void
    {
        if ($this->diagnosticContext->mode === 'disabled') {
            return;
        }

        $decision = $this->engine->evaluate($request, $this->diagnosticContext);
        if ($this->diagnosticContext->mode === 'enforce' && $decision->decision !== 'allow') {
            throw new PolicyException(
                $decision->reasonCode ?? 'configuration_invalid',
            );
        }
    }

    public function tokenFactory(): ?SecureHttpClientFactory
    {
        return $this->oauthTokenFactory ?? ($this->diagnosticContext->mode === 'enforce' ? null : $this->legacyFactory);
    }

    public function validateRawUri(string $uri): bool
    {
        if ($this->diagnosticContext->mode === 'disabled') {
            return true;
        }

        try {
            (new TargetNormalizer())->assertRawUri($uri);

            return true;
        } catch (PolicyException $error) {
            $this->engine->reportDiagnostic(
                $this->diagnosticContext,
                $this->diagnosticContext->mode === 'observe' ? 'would_deny' : 'deny',
                $error->reasonCode(),
            );
            if ($this->diagnosticContext->mode === 'enforce') {
                throw $error;
            }

            return false;
        }
    }

    /**
     * @return GuardMiddleware|null
     */
    private function observationMiddleware(): ?callable
    {
        if ($this->diagnosticContext->mode !== 'observe') {
            return null;
        }

        return function (callable $next): callable {
            /** @var GuardHandler $next */
            return function (
                RequestInterface $request,
                array $options,
            ) use ($next): PromiseInterface {
                $this->engine->evaluate($request, $this->diagnosticContext);

                return $next($request, $options);
            };
        };
    }
}
