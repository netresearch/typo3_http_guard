<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */
declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = require __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/php-cs-fixer/rules.php';
// Behavior migrations are reviewed separately through Rector.
$rules['@PHP8x2Migration']        = false;
$rules['no_alias_functions']      = false;
$rules['global_namespace_import'] = ['import_classes' => true, 'import_constants' => false, 'import_functions' => false];
$rules['declare_parentheses']     = true;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setFinder(
        Finder::create()->in([__DIR__ . '/Classes', __DIR__ . '/Configuration', __DIR__ . '/Tests', __DIR__ . '/Build'])->exclude(['Reports', 'certificates', 'vendor', '.Build'])->append(
            [__DIR__ . '/ext_localconf.php', __FILE__],
        ),
    );
