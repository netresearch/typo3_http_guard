<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);
$guardLibrarySource = dirname(__DIR__, 2) . '/.Build/http-guard-library/src';
if (is_dir($guardLibrarySource)) {
    spl_autoload_register(
        static function (string $class) use ($guardLibrarySource): void {
            $prefix = 'Netresearch\HttpGuard\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $path = $guardLibrarySource . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        },
    );
}
