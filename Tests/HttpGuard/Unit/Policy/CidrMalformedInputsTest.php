<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\Cidr;
use Netresearch\HttpGuard\PolicyException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CidrMalformedInputsTest extends TestCase
{
    #[DataProvider('malformedAddresses')]
    public function testMalformedCidrRaisesBoundedPolicyError(string $input, bool $strict): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Outbound HTTP policy: configuration_invalid');
        Cidr::parse($input, $strict);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function malformedAddresses(): iterable
    {
        foreach ([
            'nul-only'      => "\x00/0",
            'nul-ipv4'      => "127.0.0.1\x00/32",
            'nul-ipv6'      => "::1\x00/128",
            'non-ascii'     => "\x80/0",
            'newline'       => "10.0.0.1\n/32",
            'numeric-alias' => '0x7f000001/8',
        ] as $name => $input) {
            yield $name . '-strict' => [$input, true];
            yield $name . '-permissive' => [$input, false];
        }
    }
}
