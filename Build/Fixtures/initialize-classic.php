<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

use Netresearch\HttpGuard\GuardConfig;
use Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;

$fixture = realpath($argv[1] ?? '');
if ($fixture === false || !is_dir($fixture . '/typo3conf/ext/nr_http_guard')) {
    throw new RuntimeException('A prepared classic extension fixture is required.');
}
putenv('TYPO3_PATH_ROOT=' . $fixture);
putenv('TYPO3_PATH_APP=' . $fixture);
$loader = require $fixture . '/vendor/autoload.php';
SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
if (Environment::isComposerMode()) {
    throw new RuntimeException('Classic qualification must use the native package manager.');
}

// Match native extension activation before the first ordinary production boot.
$container      = Bootstrap::init($loader, true);
$packageManager = $container->get(PackageManager::class);
if (!$packageManager instanceof PackageManager) {
    throw new RuntimeException('Unexpected native package manager.');
}
if ($packageManager->isPackageActive('nr_http_guard')) {
    throw new RuntimeException('Classic fixture must begin with the installed extension inactive.');
}
$packageManager->activatePackage('nr_http_guard');
ClassLoadingInformation::dumpClassLoadingInformation();
$packageStates = require $fixture . '/typo3conf/PackageStates.php';
if (($packageStates['packages']['nr_http_guard']['packagePath'] ?? null) !== 'typo3conf/ext/nr_http_guard/') {
    throw new RuntimeException('Native classic extension activation was not persisted.');
}
if (!$packageManager->isPackageActive('nr_http_guard') || !ClassLoadingInformation::isClassLoadingInformationAvailable() || !class_exists(RequestFactoryCompatibility::class) || !class_exists(GuardConfig::class)) {
    throw new RuntimeException('Native classic extension activation failed.');
}
echo json_encode(
    [
        'status'                         => 'PASS',
        'core'                           => (new Typo3Version())->getVersion(),
        'extension_active'               => true,
        'native_class_loading_generated' => true,
        'composer_mode'                  => false,
        'extension_initially_inactive'   => true,
        'package_states_persisted'       => true,
        'package_states_sha256'          => hash_file('sha256', $fixture . '/typo3conf/PackageStates.php'),
    ],
    JSON_THROW_ON_ERROR,
) . "\n";
