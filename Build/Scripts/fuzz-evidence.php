<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

/** @return non-empty-list<int> */
function fuzzSeeds(string $manifestPath): array
{
    $text = file_get_contents($manifestPath);
    if ($text === false) {
        throw new RuntimeException('Cannot read offline fuzz manifest.');
    }
    $manifest = json_decode($text, flags: JSON_THROW_ON_ERROR);
    $seeds    = $manifest instanceof stdClass ? $manifest->seeds ?? null : null;
    if (!is_array($seeds) || !array_is_list($seeds) || $seeds === []) {
        throw new RuntimeException('Offline fuzz seeds must be a nonempty list.');
    }
    $seen = [];
    foreach ($seeds as $seed) {
        if (!is_int($seed) || $seed < 1 || $seed > 4294967295 || isset($seen[$seed])) {
            throw new RuntimeException('Offline fuzz seeds must be distinct positive uint32 integers.');
        }
        $seen[$seed] = true;
    }

    return $seeds;
}

function completedFuzzCalls(string $runRoot, int $expected): int
{
    $crashes = glob($runRoot . '/crash-*.txt');
    $text    = file_get_contents($runRoot . '/fuzz.log');
    if ($crashes === false || $text === false || $crashes !== [] || preg_match('/^(?:CORPUS CRASH|CRASH|DUPLICATE CRASH)(?: |$)/m', $text) === 1) {
        throw new RuntimeException('Offline fuzz crash or unreadable evidence.');
    }
    if (preg_match_all('/^HTTP_GUARD_FUZZ_TARGET_CALLS=([0-9]+)$/m', $text, $markers) < 1) {
        throw new RuntimeException('Offline fuzz completion marker is missing.');
    }
    $calls = (int) $markers[1][array_key_last($markers[1])];
    // Upstream can overshoot its requested maximum by four inner-loop calls.
    if ($calls < $expected || $calls > $expected + 4) {
        throw new RuntimeException('Offline fuzzing did not execute its requested case count.');
    }

    return $calls;
}

try {
    if (($argv[1] ?? null) === 'seeds' && count($argv) === 3) {
        foreach (fuzzSeeds($argv[2]) as $seed) {
            printf("%d\n", $seed);
        }
    } elseif (($argv[1] ?? null) === 'verify' && count($argv) === 4 && ctype_digit($argv[3]) && (int) $argv[3] > 0) {
        printf("%d\n", completedFuzzCalls($argv[2], (int) $argv[3]));
    } else {
        throw new RuntimeException('Usage: fuzz-evidence.php seeds MANIFEST | verify RUN_DIRECTORY EXPECTED_CALLS');
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
