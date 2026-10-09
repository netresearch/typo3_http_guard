<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\Cidr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CidrNetworkContractTest extends TestCase
{
    #[DataProvider('networkRelations')]
    public function testNetworkContainmentRespectsFamilyPrefixAndBinaryEdges(
        string $outer,
        string $inner,
        bool $expected,
    ): void {
        self::assertSame($expected, Cidr::parse($outer)->containsNetwork(Cidr::parse($inner)));
    }

    #[DataProvider('canonicalNetworks')]
    public function testValidNetworkPreservesCanonicalMetadata(
        string $input,
        bool $strict,
        string $canonical,
        int $prefix,
        int $family,
        string $networkHex,
    ): void {
        $network = Cidr::parse($input, $strict);
        self::assertSame($canonical, $network->cidr);
        self::assertSame($prefix, $network->prefix);
        self::assertSame($family, $network->family);
        self::assertSame($networkHex, bin2hex($network->network));
    }

    #[DataProvider('canonicalAddresses')]
    public function testOrdinaryIpv4AndIpv6AddressFormsHaveStableCanonicalText(
        string $input,
        string $expected,
    ): void {
        self::assertSame($expected, Cidr::address($input));
    }

    public function testZeroPrefixAcceptsEitherFamilyOnlyWithinItsOwnPackedLength(): void
    {
        $v4 = Cidr::parse('0.0.0.0/0');
        $v6 = Cidr::parse('::/0');
        self::assertTrue($v4->containsPacked(hex2bin('ffffffff')));
        self::assertTrue($v6->containsPacked(str_repeat(chr(255), 16)));
        self::assertFalse($v4->containsPacked(str_repeat(chr(0), 16)));
        self::assertFalse($v6->containsPacked(str_repeat(chr(0), 4)));
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function networkRelations(): iterable
    {
        yield 'v4 equal' => ['10.16.0.0/12', '10.16.0.0/12', true];
        yield 'v4 narrower' => ['10.16.0.0/12', '10.31.255.0/24', true];
        yield 'v4 adjacent' => ['10.16.0.0/12', '10.32.0.0/12', false];
        yield 'v4 broader' => ['10.16.0.0/12', '10.0.0.0/8', false];
        yield 'v4 zero covers host' => ['0.0.0.0/0', '255.255.255.255/32', true];
        yield 'v4 host cannot cover subnet' => ['10.0.0.0/32', '10.0.0.0/24', false];
        yield 'v6 equal' => ['2001:db8::/32', '2001:db8::/32', true];
        yield 'v6 partial narrower' => ['2001:db8::/33', '2001:db8:7fff::/48', true];
        yield 'v6 partial adjacent' => ['2001:db8::/33', '2001:db8:8000::/33', false];
        yield 'v6 broader' => ['2001:db8::/33', '2001:db8::/32', false];
        yield 'v6 zero covers host' => ['::/0', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff/128', true];
        yield 'family v4 versus v6' => ['0.0.0.0/0', '::/0', false];
        yield 'family v6 versus v4' => ['::/0', '0.0.0.0/0', false];
    }

    /** @return iterable<string, array{string, bool, string, int, int, string}> */
    public static function canonicalNetworks(): iterable
    {
        yield 'IPv4 zero' => ['0.0.0.0/0', true, '0.0.0.0/0', 0, 4, '00000000'];
        yield 'IPv4 partial' => ['10.16.0.0/13', true, '10.16.0.0/13', 13, 4, '0a100000'];
        yield 'IPv4 host' => ['255.255.255.255/32', true, '255.255.255.255/32', 32, 4, 'ffffffff'];
        yield 'IPv6 zero' => ['::/0', true, '::/0', 0, 6, str_repeat('00', 16)];
        yield 'IPv6 partial' => ['2001:db8::/33', true, '2001:db8::/33', 33, 6, '20010db8000000000000000000000000'];
        yield 'IPv6 host' => ['2001:db8::1/128', true, '2001:db8::1/128', 128, 6, '20010db8000000000000000000000001'];
        yield 'mapped zero' => ['::ffff:0.0.0.0/96', true, '0.0.0.0/0', 0, 4, '00000000'];
        yield 'mapped partial' => ['::ffff:128.0.0.0/97', true, '128.0.0.0/1', 1, 4, '80000000'];
        yield 'mapped host' => ['::ffff:192.0.2.1/128', true, '192.0.2.1/32', 32, 4, 'c0000201'];
        yield 'permissive IPv6 spelling' => [
            '2001:0DB8:0000:0000:0000:0000:0000:0000/32',
            false,
            '2001:db8::/32',
            32,
            6,
            '20010db8000000000000000000000000',
        ];
    }

    /** @return iterable<string, array{string, string}> */
    public static function canonicalAddresses(): iterable
    {
        yield 'ordinary v4' => ['192.0.2.1', '192.0.2.1'];
        yield 'mapped v4' => ['::ffff:192.0.2.1', '192.0.2.1'];
        yield 'hex mapped v4' => ['::FFFF:C000:0201', '192.0.2.1'];
        yield 'IPv4 compatible v6' => ['::192.0.2.1', '::c000:201'];
        yield 'expanded v6' => ['2001:0DB8:0000:0000:0000:0000:0000:0001', '2001:db8::1'];
        yield 'IPv6 zero' => ['::', '::'];
    }
}
