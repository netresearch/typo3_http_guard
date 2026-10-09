<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

$root               = dirname(__DIR__, 2);
$configuredAutoload = getenv('HTTP_GUARD_TEST_AUTOLOAD');
$autoload           = $configuredAutoload !== false && $configuredAutoload !== '' ? $configuredAutoload : $root . '/.Build/vendor/autoload.php';
/**
 * Composer returns its ClassLoader on every require; require_once may return true.
 *
 * @SuppressWarnings("php:S2003")
 */
$loader = require $autoload;
if (!$loader instanceof Composer\Autoload\ClassLoader) {
    throw new RuntimeException('Architecture tests require the project Composer loader');
}
$loader->setPsr4('Netresearch\NrHttpGuard\\', $root . '/Classes');
$loader->setPsr4('Netresearch\HttpGuard\\', $root . '/Classes/HttpGuard');
$loader->setPsr4('Netresearch\NrHttpGuard\Tests\\', $root . '/Tests');
$loader->setPsr4('Netresearch\HttpGuard\Tests\\', $root . '/Tests/HttpGuard');
require_once $root . '/Tests/bootstrap.php';
