<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Http;

use Netresearch\HttpGuard\PolicyException;
use TYPO3\CMS\Core\Http\RequestFactory;

final readonly class RawRequestFactoryRegistration
{
    /**
     * @param \Closure(): mixed $factory
     * @param \Closure(): mixed $psrFactory
     */
    public function __construct(
        private \Closure $factory,
        private \Closure $psrFactory
    )
    {
    }
    public function assertValid(): void
    {
        RequestFactoryCompatibility::assertSupported();
        $expected = RequestFactoryCompatibility::replacementClass();
        $mapping = $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][RequestFactory::class] ?? null;
        if (!is_array($mapping) || ($mapping['className'] ?? null) !== $expected) {
            throw new PolicyException('configuration_invalid');
        }
        $factory = ($this->factory)();
        if (!is_object($factory) || $factory::class !== $expected || ($this->psrFactory)() !== $factory) {
            throw new PolicyException('configuration_invalid');
        }
    }
}
