<?php

declare (strict_types=1);

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 *
 * Tailor 1.x applies directory rules to relative paths and file rules to
 * basenames. Keep these package boundaries aligned with build-extension.py.
 */
$root = dirname(__DIR__);
$rootFiles = [
    'composer.json',
    'ext_emconf.php',
    'ext_localconf.php',
    'README.md',
    'LICENSE.txt',
    'LICENSE-HttpGuard.txt',
    'LICENSES.md',
];
$corpus = 'Resources/Private/HttpGuard/data/security-corpus';
$corpusFiles = [
    'README.md',
    'address-rules.json',
    'address-cases.json',
    'endpoint-cases.json',
    'uri-cases.json',
    'scenario-cases.json',
    'sources.json',
];

$excludedFiles = [];
foreach ([[$root, $rootFiles], [$root . '/' . $corpus, $corpusFiles]] as [$directory, $allowedFiles]) {
    $entries = scandir($directory);
    if ($entries === false) {
        throw new RuntimeException('Cannot read packaging source: ' . $directory);
    }
    foreach ($entries as $filename) {
        if (is_file($directory . '/' . $filename) && !in_array($filename, $allowedFiles, true)) {
            $excludedFiles[] = '^' . preg_quote($filename, '/');
        }
    }
}

return [
    'directories' => [
        '(?!(?:Classes|Configuration|Documentation|Resources)(?:\/|$))',
        'Resources\/Private\/HttpGuard\/data\/security-corpus\/',
    ],
    'files' => array_values(array_unique($excludedFiles)),
];
