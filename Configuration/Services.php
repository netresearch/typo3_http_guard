<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);
use Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use TYPO3\CMS\Core\Http\RequestFactory;

return static function (ContainerConfigurator $container): void {
    $services    = $container->services();
    $replacement = RequestFactoryCompatibility::replacementClass();
    $inner       = $replacement . '.inner';
    $services
        ->set($replacement)
        ->decorate(RequestFactory::class, $inner)
        ->autowire()
        ->autoconfigure()
        ->public()
        ->arg('$originalFactory', service($inner));
    $services->alias(RequestFactoryInterface::class, $replacement)->public();
};
