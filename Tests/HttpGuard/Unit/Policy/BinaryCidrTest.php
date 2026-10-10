<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\Cidr;
use PHPUnit\Framework\TestCase;

final class BinaryCidrTest extends TestCase
{
    public function testMappedPrivateAddressMatchesIpv4Network(): void
    {
        self::assertTrue(Cidr::parse('10.23.4.0/24')->contains('::ffff:10.23.4.12'));
        self::assertFalse(Cidr::parse('10.23.4.0/24')->contains('::ffff:10.23.5.12'));
    }

    public function testNetworkEdgesAreBinaryAndFamilySpecific(): void
    {
        $cidr = Cidr::parse('2001:4860::/32');
        self::assertTrue($cidr->contains('2001:4860:ffff:ffff:ffff:ffff:ffff:ffff'));
        self::assertFalse($cidr->contains('2001:4861::'));
        self::assertFalse($cidr->contains('8.8.8.8'));
    }

    public function testNonNetworkCanonicalCidrIsRejected(): void
    {
        $this->expectException(\Netresearch\HttpGuard\PolicyException::class);
        Cidr::parse('10.23.4.12/24');
    }
}
