<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Http;

use Closure;
use Netresearch\HttpGuard\PolicyException;
use TYPO3\CMS\Core\Http\RequestFactory;

final readonly class RawRequestFactoryRegistration
{
    /**
     * @param Closure(): mixed $factory
     * @param Closure(): mixed $psrFactory
     */
    public function __construct(private Closure $factory, private Closure $psrFactory) {}

    public function assertValid(): void
    {
        RequestFactoryCompatibility::assertSupported();
        $expected = RequestFactoryCompatibility::replacementClass();
        $mapping  = $this->objectMapping(RequestFactory::class);
        if (!is_array($mapping) || ($mapping['className'] ?? null) !== $expected) {
            throw new PolicyException('configuration_invalid');
        }
        $factory = ($this->factory)();
        if (!is_object($factory) || $factory::class !== $expected || ($this->psrFactory)() !== $factory) {
            throw new PolicyException('configuration_invalid');
        }
    }

    private function objectMapping(string $class): mixed
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        if (!is_array($configuration)) {
            throw new PolicyException('configuration_invalid');
        }
        $system = $configuration['SYS'] ?? null;
        if (!is_array($system)) {
            throw new PolicyException('configuration_invalid');
        }
        $objects = $system['Objects'] ?? null;
        if (!is_array($objects)) {
            throw new PolicyException('configuration_invalid');
        }

        return $objects[$class] ?? null;
    }
}
