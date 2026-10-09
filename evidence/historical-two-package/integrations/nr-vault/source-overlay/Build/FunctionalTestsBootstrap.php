<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

use TYPO3\TestingFramework\Core\Testbase;

/**
 * Bootstrap for functional tests.
 *
 * Uses TYPO3's testing framework to set up an isolated test environment
 * with its own database and file system.
 */

// Load Composer autoloader
$autoloadFile = dirname(__DIR__) . '/.Build/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    $autoloadFile = dirname(__DIR__, 4) . '/vendor/autoload.php';
}

if (!file_exists($autoloadFile)) {
    throw new RuntimeException(
        'Could not find autoload.php. Please run "composer install" first.',
        5849523198,
    );
}

require_once $autoloadFile;
require_once dirname(__DIR__) . '/Tests/Fixtures/HttpGuardBootstrap.php';

// Initialize the testing framework
$testbase = new Testbase();
$testbase->defineOriginalRootPath();
$testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/tests');
$testbase->createDirectory(ORIGINAL_ROOT . 'typo3temp/var/transient');
