<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/Tests/Architecture/bootstrap.php';
$parent             = new ReflectionClass(TYPO3\CMS\Core\Http\RequestFactory::class);
$unavailableFactory = $parent->isReadOnly() ? Netresearch\NrHttpGuard\Http\GuardedRequestFactory13::class : Netresearch\NrHttpGuard\Http\GuardedRequestFactory14::class;
// Infection reflects every source class before generating mutants. The adapters
// have mutually exclusive parent ABIs. Its ReflectionException fallback uses the
// real parent metadata without loading the incompatible child; neither source
// file is excluded from mutation generation or from the uncovered denominator.
spl_autoload_register(
    static function (string $class) use ($unavailableFactory): void {
        if ($class === $unavailableFactory) {
            throw new ReflectionException('Request factory ABI is unavailable in this mutation generation process');
        }
    },
    true,
    true,
);
