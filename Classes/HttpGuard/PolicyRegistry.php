<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use WeakMap;

final class PolicyRegistry
{
    /**
     * @var WeakMap<RequestPolicyContext,array{scope:ClientScope,grant:?EndpointGrant,profile:?EndpointProfile}>
     */
    private WeakMap $contexts;

    public function __construct(private readonly GuardConfig $config, private readonly ClockInterface $clock)
    {
        $this->contexts    = new WeakMap();
        $this->scopeIssuer = Closure::bind(static fn (): ClientScope => new ClientScope(), null, ClientScope::class);
        $this->grantIssuer = Closure::bind(static fn (): EndpointGrant => new EndpointGrant(), null, EndpointGrant::class);
    }

    public function configuration(): GuardConfig
    {
        return $this->config;
    }

    public function newContext(?string $endpointId = null): RequestPolicyContext
    {
        $profile = null;
        if ($endpointId !== null) {
            $entry = $this->config->data['endpoints'][$endpointId] ?? null;
            if ($entry === null) {
                throw new PolicyException('grant_invalid');
            }
            $profile = new EndpointProfile(
                $endpointId,
                $entry['origin'],
                $entry['allowedCidrs'],
                $entry['methods'],
                $entry['redirects'],
                $entry['allowLoopback'],
                $entry['purpose'],
                $entry['owner'],
                $entry['reviewAfter'] === null ? null : new DateTimeImmutable($entry['reviewAfter'], new DateTimeZone('UTC')),
                $entry['expiresAt'] === null ? null : new DateTimeImmutable($entry['expiresAt']),
            );
        }
        $scope                    = ($this->scopeIssuer)();
        $grant                    = $profile instanceof EndpointProfile ? ($this->grantIssuer)() : null;
        $context                  = new RequestPolicyContext($this->config->mode, $this->config->revision, $scope, $grant);
        $this->contexts[$context] = ['scope' => $scope, 'grant' => $grant, 'profile' => $profile];

        return $context;
    }

    public function validateContext(RequestPolicyContext $context): ?EndpointProfile
    {
        if (!isset($this->contexts[$context]) || $context->policyRevision !== $this->config->revision || $context->mode !== $this->config->mode) {
            throw new PolicyException('grant_invalid');
        }
        $owned = $this->contexts[$context];
        if ($owned['scope'] !== $context->clientScope || $owned['grant'] !== $context->endpointGrant) {
            throw new PolicyException('grant_invalid');
        }
        $profile = $owned['profile'];
        if ($profile !== null && $profile->expiresAt !== null && $this->clock->now() >= $profile->expiresAt) {
            throw new PolicyException('grant_invalid');
        }

        return $profile;
    }
    /**
     * @var Closure():ClientScope
     */
    private readonly Closure $scopeIssuer;
    /**
     * @var Closure():EndpointGrant
     */
    private readonly Closure $grantIssuer;
}
