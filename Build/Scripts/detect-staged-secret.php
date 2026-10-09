<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

// This development helper receives index blob bytes on stdin, never file paths.
try {
    require_once __DIR__ . '/../../.Build/vendor/autoload.php';
    $detector = CaptainHook\Secrets\Detector::create()
        ->useSupplierConfig(
            [
                CaptainHook\Secrets\Regex\Supplier\Aws::class,
                CaptainHook\Secrets\Regex\Supplier\GitHub::class,
                CaptainHook\Secrets\Regex\Supplier\Gitlab::class,
                CaptainHook\Secrets\Regex\Supplier\Google::class,
                CaptainHook\Secrets\Regex\Supplier\Password::class,
                CaptainHook\Secrets\Regex\Supplier\Stripe::class,
            ],
        )
        ->useRegex('#-----BEGIN (?:[A-Z0-9 ]*PRIVATE KEY|PGP PRIVATE KEY BLOCK)-----#');
    $input = file_get_contents('php://stdin');
    if ($input === false) {
        throw new RuntimeException('input-unavailable');
    }
    $blobs = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($blobs) || !array_is_list($blobs)) {
        throw new RuntimeException('invalid-input');
    }
    $results = [];
    foreach ($blobs as $encoded) {
        if (!is_string($encoded)) {
            throw new RuntimeException('invalid-blob');
        }
        $blob = base64_decode($encoded, true);
        if ($blob === false) {
            throw new RuntimeException('invalid-encoding');
        }
        $results[] = $detector->detectIn($blob)->wasSecretDetected();
    }
    echo json_encode($results, JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR, "Secret detector failed.\n");
    exit(2);
}
