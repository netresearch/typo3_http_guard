<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH.
 */
declare(strict_types=1);

// Verify the actual resolved graph, before any qualification matrix overrides.
$autoload = $argc === 2 ? realpath($argv[1]) : false;
if ($autoload === false || !is_file($autoload)) {
    fwrite(STDERR, "FAIL: a local Composer autoloader is required.\n");
    exit(1);
}
require $autoload;
$sourceRoot = dirname(__DIR__, 2);
foreach ([
    '/Classes/HttpGuard/OutboundPolicyExceptionInterface.php',
    '/Classes/HttpGuard/PolicyException.php',
    '/Classes/HttpGuard/Transport/RuntimeSupport.php',
    '/Classes/Http/RequestFactoryCompatibility.php',
] as $source) {
    require_once $sourceRoot . $source;
}

Netresearch\HttpGuard\Transport\RuntimeSupport::assertSupported();
Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility::assertSupported();
echo "Resolved Composer graph matches the qualified runtime contracts.\n";
