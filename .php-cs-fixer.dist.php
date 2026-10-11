<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$scope = getenv('HTTP_GUARD_CGL_SCOPE') ?: 'extension';
if (!in_array($scope, ['extension', 'kernel'], true)) {
    throw new InvalidArgumentException('Unsupported coding-style scope');
}
$kernel = $scope === 'kernel';
$rules  = require_once __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/php-cs-fixer/rules.php';
// Behavior migrations are reviewed separately through Rector.
$rules['@PHP8x2Migration']        = false;
$rules['no_alias_functions']      = false;
$rules['global_namespace_import'] = ['import_classes' => true, 'import_constants' => false, 'import_functions' => false];
$rules['declare_parentheses']     = true;
$rules                            = array_merge(
    $rules,
    [
        '@PER-CS3x0:risky' => true,
        'header_comment'   => [
            'header'       => 'SPDX-License-Identifier: ' . ($kernel ? 'MIT' : 'GPL-2.0-or-later') . "\nSPDX-FileCopyrightText: 2026 Netresearch DTT GmbH",
            'comment_type' => 'comment',
            'location'     => 'after_open',
            'separate'     => 'both',
        ],
    ],
);
$finder = $kernel ? Finder::create()->in(__DIR__ . '/Classes/HttpGuard') : Finder::create()
    ->in([__DIR__ . '/Configuration', __DIR__ . '/Tests', __DIR__ . '/Build'])
    ->exclude(['Reports', 'certificates', 'vendor', '.Build'])
    ->append(Finder::create()->in(__DIR__ . '/Classes')->exclude('HttpGuard'))
    ->append([__DIR__ . '/ext_localconf.php', __DIR__ . '/rector.php', __FILE__]);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.' . $scope . '.cache')
    ->setFinder($finder);
