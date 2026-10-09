<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);
$guardExtensionSource = dirname(__DIR__, 2) . '/.Build/nr-http-guard-extension/Classes/HttpGuard';
if (is_dir($guardExtensionSource)) {
    spl_autoload_register(
        static function (string $class) use ($guardExtensionSource): void {
            $prefix = 'Netresearch\HttpGuard\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $path = $guardExtensionSource . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        },
    );
}
