<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures;

final class AddressRulesBoundaryState
{
    public static string|false $payload   = '';
    public static bool $exists            = true;
    public static string|false $rulesHash = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
}

namespace Netresearch\HttpGuard;

use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\AddressRulesBoundaryState as State;

function is_file(string $path): bool
{
    return State::$exists;
}
function file_get_contents(
    string $path,
    bool $useIncludePath = false,
    $context = null,
    int $offset = 0,
    ?int $length = null,
): string|false {
    return State::$payload;
}
function hash_file(string $algorithm, string $filename, bool $binary = false): string|false
{
    return State::$rulesHash;
}
