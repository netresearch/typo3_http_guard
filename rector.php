<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/Classes', __DIR__ . '/Configuration'])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([LevelSetList::UP_TO_PHP_82, SetList::CODE_QUALITY, SetList::DEAD_CODE])
    ->withSkip(
        [
            // TYPO3 discovers the listener from its typed event parameter.
            RemoveUnusedPublicMethodParameterRector::class => [__DIR__ . '/Classes/EventListener/RegisterGuardListener.php'],
            // The standalone kernel recognizes a foreign middleware by opaque name.
            StringClassNameToClassConstantRector::class => [__DIR__ . '/Classes/HttpGuard/Client/GuardedClientFactory.php'],
        ],
    )
    ->withCache(__DIR__ . '/.rector-cache');
