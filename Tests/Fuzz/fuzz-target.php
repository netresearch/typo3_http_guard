<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

/** @var PhpFuzzer\Config $config */
require_once dirname(__DIR__) . '/bootstrap.php';
$seed = (int) (getenv('HTTP_GUARD_FUZZ_SEED') ?: '104729');
mt_srand($seed, MT_RAND_MT19937);
$properties  = new Netresearch\NrHttpGuard\Tests\Fuzz\OfflineProperties();
$targetCalls = 0;
$config->setTarget(
    static function (string $input) use ($properties, &$targetCalls): void {
        ++$targetCalls;
        $properties->exercise($input);
    },
);
register_shutdown_function(
    static function () use (&$targetCalls): void {
        printf('HTTP_GUARD_FUZZ_TARGET_CALLS=%d' . PHP_EOL, $targetCalls);
    },
);
$config->setAllowedExceptions([]);
$config->setMaxLen(2048);
