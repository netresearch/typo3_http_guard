<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use DateTimeImmutable;
use Netresearch\HttpGuard\ClockInterface;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\RequestPolicyContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeakReference;

final class RegistryTransitionClock implements ClockInterface
{
    public DateTimeImmutable $instant;

    public function __construct(string $instant = '2026-10-09T11:59:59.999999Z')
    {
        $this->instant = new DateTimeImmutable($instant);
    }

    public function now(): DateTimeImmutable
    {
        return $this->instant;
    }

    public function monotonic(): float
    {
        return 0;
    }
}
final class PolicyRegistryContractTest extends TestCase
{
    #[DataProvider('modes')]
    public function testIssuedContextsPreserveConfigurationAndDistinctScopeForEachLifetime(string $mode): void
    {
        $config   = GuardConfig::fromArray(['mode' => $mode]);
        $registry = new PolicyRegistry($config, new RegistryTransitionClock());
        $first    = $registry->newContext();
        $second   = $registry->newContext();
        self::assertSame($config, $registry->configuration());
        self::assertSame($mode, $first->mode);
        self::assertSame($config->revision, $first->policyRevision);
        self::assertNotSame($first, $second);
        self::assertNotSame($first->clientScope, $second->clientScope);
        self::assertNull($first->endpointGrant);
        self::assertNull($registry->validateContext($first));
        self::assertNull($registry->validateContext($second));
    }

    /** @return iterable<string,array{string}> */
    public static function modes(): iterable
    {
        yield 'enforce' => ['enforce'];
        yield 'observe' => ['observe'];
        yield 'disabled' => ['disabled'];
    }

    public function testEndpointProfilePreservesReviewAndExpiryInstantsWithoutCreatingAReviewGate(): void
    {
        $config   = GuardConfig::fromArray(['endpoints' => ['erp' => $this->endpoint()]]);
        $clock    = new RegistryTransitionClock();
        $registry = new PolicyRegistry($config, $clock);
        $first    = $registry->newContext('erp');
        $second   = $registry->newContext('erp');
        $profile  = $registry->validateContext($first);
        self::assertNotNull($profile);
        self::assertSame('erp', $profile->id);
        self::assertSame('https://erp.test', $profile->origin);
        self::assertSame(['10.23.5.12/32'], $profile->allowedCidrs);
        self::assertSame(['GET', 'POST'], $profile->methods);
        self::assertSame('same-origin', $profile->redirects);
        self::assertFalse($profile->allowLoopback);
        self::assertSame('Registry lifecycle fixture', $profile->purpose);
        self::assertSame('Test maintainers', $profile->owner);
        self::assertSame('2026-10-08T00:00:00+00:00', $profile->reviewAfter?->format(DATE_ATOM));
        self::assertSame('2026-10-09T14:00:00+02:00', $profile->expiresAt?->format(DATE_ATOM));
        self::assertNotNull($first->endpointGrant);
        self::assertNotSame($first->endpointGrant, $second->endpointGrant);
        self::assertNotSame($profile, $registry->validateContext($second));
        $clock->instant = new DateTimeImmutable('2026-10-09T12:00:00Z');
        try {
            $registry->validateContext($first);
            self::fail('Expiry is inclusive across equal timezone instants');
        } catch (PolicyException $failure) {
            self::assertSame('grant_invalid', $failure->reasonCode());
        }
        $this->expectException(PolicyException::class);
        $registry->validateContext($second);
    }

    public function testUnknownEndpointCannotBecomeAPublicContext(): void
    {
        $registry = new PolicyRegistry(
            GuardConfig::fromArray(['endpoints' => ['erp' => $this->endpoint()]]),
            new RegistryTransitionClock(),
        );
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('grant_invalid');
        $registry->newContext('missing');
    }

    #[DataProvider('foreignContexts')]
    public function testOnlyIssuedObjectIdentityCarriesAuthority(string $kind): void
    {
        $config   = GuardConfig::fromArray(['endpoints' => ['erp' => $this->endpoint()]]);
        $clock    = new RegistryTransitionClock();
        $registry = new PolicyRegistry($config, $clock);
        $context  = $registry->newContext('erp');
        $foreign  = match ($kind) {
            'clone'           => clone $context,
            'borrowed-tokens' => new RequestPolicyContext(
                $context->mode,
                $context->policyRevision,
                $context->clientScope,
                $context->endpointGrant,
            ),
            'other-registry' => (new PolicyRegistry($config, $clock))->newContext('erp'),
        };
        self::assertNotNull($registry->validateContext($context));
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('grant_invalid');
        $registry->validateContext($foreign);
    }

    /** @return iterable<string,array{string}> */
    public static function foreignContexts(): iterable
    {
        yield 'cloned object' => ['clone'];
        yield 'tokens borrowed into new object' => ['borrowed-tokens'];
        yield 'same config from other issuer' => ['other-registry'];
    }

    public function testContextAndItsUnreferencedTokensAreNotRetainedByTheRegistry(): void
    {
        $registry = new PolicyRegistry(
            GuardConfig::fromArray(['endpoints' => ['erp' => $this->endpoint()]]),
            new RegistryTransitionClock(),
        );
        $context          = $registry->newContext('erp');
        $contextReference = WeakReference::create($context);
        $scopeReference   = WeakReference::create($context->clientScope);
        $grantReference   = WeakReference::create($context->endpointGrant);
        unset($context);
        gc_collect_cycles();
        self::assertNull($contextReference->get());
        self::assertNull($scopeReference->get());
        self::assertNull($grantReference->get());
        $new = $registry->newContext('erp');
        self::assertNotNull($registry->validateContext($new));
    }

    /** @return array<string,mixed> */
    private function endpoint(): array
    {
        return [
            'origin'       => 'https://erp.test',
            'allowedCidrs' => ['10.23.5.12/32'],
            'methods'      => ['GET', 'POST'],
            'redirects'    => 'same-origin',
            'purpose'      => 'Registry lifecycle fixture',
            'owner'        => 'Test maintainers',
            'reviewAfter'  => '2026-10-08',
            'expiresAt'    => '2026-10-09T14:00:00+02:00',
        ];
    }
}
