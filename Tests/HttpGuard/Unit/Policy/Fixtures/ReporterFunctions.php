<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures;

use LogicException;

final class ReporterFunctionProbe
{
    /** @var list<int> */
    public static array $draws = [];
    /** @var list<array{int,int}> */
    public static array $ranges = [];
    public static int $wipes    = 0;

    public static function draw(int $min, int $max): int
    {
        self::$ranges[] = [$min, $max];
        $value          = array_shift(self::$draws);
        if (!is_int($value) || $value < $min || $value > $max) {
            throw new LogicException('Reporter requested an unexpected entropy draw');
        }

        return $value;
    }
}

namespace Netresearch\HttpGuard;

use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\ReporterFunctionProbe;

function random_int(int $min, int $max): int
{
    return ReporterFunctionProbe::draw($min, $max);
}
function sodium_memzero(string &$secret): void
{
    ++ReporterFunctionProbe::$wipes;
    \sodium_memzero($secret);
}
