<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Composer\Semver\Semver;
use TYPO3\CMS\Core\Information\Typo3Version;

if ($argc !== 4) {
    fwrite(STDERR, "Expected autoloader, PHP minor and optional Core constraint.\n");
    exit(2);
}
require_once $argv[1];
if (!class_exists(Semver::class)) {
    // Production Core fixtures may omit this development-tool dependency.
    // Register only Semver; a whole dev autoloader could load a different Core.
    $semverPath = dirname(__DIR__, 2) . '/.Build/vendor/composer/semver/src';
    if (!is_file($semverPath . '/Semver.php')) {
        fwrite(STDERR, "Install the Composer Semver development tool before runtime selection.\n");
        exit(2);
    }
    $toolLoader = new ClassLoader();
    $toolLoader->addPsr4('Composer\Semver\\', $semverPath);
    $toolLoader->register();
}
$actualPhp  = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
$actualCore = (new Typo3Version())->getVersion();
if ($actualPhp !== $argv[2] || $argv[3] !== '' && !Semver::satisfies($actualCore, $argv[3])) {
    fwrite(STDERR, "Selected PHP/Core does not match the installed runtime.\n");
    exit(2);
}
echo json_encode(
    [
        'php'      => PHP_VERSION,
        'core'     => $actualCore,
        'guzzle'   => InstalledVersions::getPrettyVersion('guzzlehttp/guzzle'),
        'promises' => InstalledVersions::getPrettyVersion('guzzlehttp/promises'),
        'psr7'     => InstalledVersions::getPrettyVersion('guzzlehttp/psr7'),
    ],
    JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
), "\n";
