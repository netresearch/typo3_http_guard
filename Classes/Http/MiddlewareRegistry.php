<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Http;

use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\Client\MiddlewarePair;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\TerminalGuardMiddleware;

final class MiddlewareRegistry
{
    public const BOUNDARY         = 'nr/http-guard-boundary';
    public const TERMINAL         = 'nr/http-guard-terminal';
    private ?MiddlewarePair $pair = null;

    public function __construct(
        private readonly GuardedClientFactory $globalFactory,
        private readonly \Netresearch\HttpGuard\GuardConfig $config,
        private readonly \Netresearch\NrHttpGuard\Configuration\GlobalHttpDefaults $defaults,
        private readonly RawRequestFactoryRegistration $rawFactory,
    ) {}

    public function register(): void
    {
        $this->rawFactory->assertValid();
        $registry = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] ?? [];
        if ($this->pair !== null || !is_array($registry) || array_key_exists(self::BOUNDARY, $registry) || array_key_exists(self::TERMINAL, $registry)) {
            throw new PolicyException('configuration_invalid');
        }
        foreach ($registry as $middleware) {
            if (!is_callable($middleware) || $middleware instanceof BoundaryMiddleware || $middleware instanceof TerminalGuardMiddleware) {
                throw new PolicyException('configuration_invalid');
            }
        }
        $pair      = $this->createPair();
        $assertion = fn (): null => $this->assertRegistry();
        $pair->boundary->setRegistryAssertion($assertion);
        $pair->terminal->setRegistryAssertion($assertion);
        $this->pair                                    = $pair;
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = [self::BOUNDARY => $pair->boundary] + $registry + [self::TERMINAL => $pair->terminal];
        $this->assertValid();
    }

    private function assertRegistry(): null
    {
        $this->assertValid();

        return null;
    }

    public function assertValid(): void
    {
        $this->rawFactory->assertValid();
        $registry = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] ?? null;
        if ($this->pair === null || !is_array($registry) || $registry === []) {
            throw new PolicyException('configuration_invalid');
        }
        $keys = array_keys($registry);
        if ($keys[0] !== self::BOUNDARY || $keys[array_key_last($keys)] !== self::TERMINAL || $registry[self::BOUNDARY] !== $this->pair->boundary || $registry[self::TERMINAL] !== $this->pair->terminal) {
            throw new PolicyException('configuration_invalid');
        }
        $boundaries = 0;
        $terminals  = 0;
        foreach ($registry as $middleware) {
            if (!is_callable($middleware)) {
                throw new PolicyException('configuration_invalid');
            }
            $boundaries += (int) ($middleware instanceof BoundaryMiddleware);
            $terminals += (int) ($middleware instanceof TerminalGuardMiddleware);
        }
        if ($boundaries !== 1 || $terminals !== 1) {
            throw new PolicyException('configuration_invalid');
        }
    }

    public function binding(): MiddlewarePair
    {
        $this->assertValid();
        if (!$this->pair instanceof MiddlewarePair) {
            throw new PolicyException('configuration_invalid');
        }

        return $this->pair;
    }

    private function createPair(): MiddlewarePair
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = $this->defaults->apply($GLOBALS['TYPO3_CONF_VARS']['HTTP'], $this->config);

        return $this->globalFactory->middlewarePair();
    }
}
