<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures;

/** Valid alternative native text formats only; real inet_pton still verifies the address bytes. */
final class CidrNativeFormatProbe
{
    /** @var array<string,string> */
    public static array $formats = [];
    /** @var list<string> */
    public static array $calls = [];
}

namespace Netresearch\HttpGuard;

use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\CidrNativeFormatProbe as Probe;

function inet_ntop(string $address): string|false
{
    Probe::$calls[] = $address;

    return Probe::$formats[bin2hex($address)] ?? \inet_ntop($address);
}
