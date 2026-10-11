<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\Cidr;
use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\CidrNativeFormatProbe as Probe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/** Public CIDR portability contracts. The formatter model is local to each PHPUnit child. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CidrNativeFormatContractTest extends TestCase
{
    #[DataProvider('equivalentNativeFormats')]
    public function testValidMixedNativeTextProducesStableCanonicalHexadecimalIpv6(
        string $input,
        string $mixedNative,
        string $canonical,
    ): void {
        $packed = \inet_pton($input);
        self::assertIsString($packed);
        self::assertSame(16, strlen($packed));
        self::assertSame(
            $packed,
            \inet_pton($mixedNative),
            'The modeled native text must denote exactly the real bytes',
        );
        $loaders = spl_autoload_functions();
        require_once __DIR__ . '/Fixtures/CidrNativeFormatProbe.php';
        self::assertSame(
            $loaders,
            spl_autoload_functions(),
            'Keep the real source or Infection mutant loader authoritative',
        );
        Probe::$formats[bin2hex($packed)] = $mixedNative;
        self::assertSame($canonical, Cidr::address($input));
        self::assertSame([$packed], Probe::$calls);
        self::assertSame($packed, \inet_pton($canonical));
    }

    /** @return iterable<string,array{string,string,string}> */
    public static function equivalentNativeFormats(): iterable
    {
        yield 'compatible address compresses first six words' => ['::203.0.115.7', '::203.0.115.7', '::cb00:7307'];
        yield 'longest run at beginning' => ['0:0:0:0:1:2:3:4', '0:0:0:0:1:2:0.3.0.4', '::1:2:3:4'];
        yield 'longest run in middle' => ['2001:db8:0:0:0:1:cb00:7307', '2001:db8:0:0:0:1:203.0.115.7', '2001:db8::1:cb00:7307'];
        yield 'longest run at end' => ['2001:db8:1:2:3:0:0:0', '2001:db8:1:2:3:0:0.0.0.0', '2001:db8:1:2:3::'];
        yield 'equal zero runs choose leftmost' => ['2001:0:0:1:0:0:cb00:7307', '2001:0:0:1:0:0:203.0.115.7', '2001::1:0:0:cb00:7307'];
        yield 'later longer zero run wins' => ['2001:0:1:0:0:0:cb00:7307', '2001:0:1:0:0:0:203.0.115.7', '2001:0:1::cb00:7307'];
        yield 'singleton zero is not compressed' => ['2001:db8:0:1:2:3:cb00:7307', '2001:db8:0:1:2:3:203.0.115.7', '2001:db8:0:1:2:3:cb00:7307'];
        yield 'no zero run preserves all words and removes leading zeros' => ['2001:0DB8:0001:0002:0003:0004:CB00:7307', '2001:db8:1:2:3:4:203.0.115.7', '2001:db8:1:2:3:4:cb00:7307'];
        yield 'all zero words' => ['::', '::0.0.0.0', '::'];
        yield 'loopback last word' => ['::1', '::0.0.0.1', '::1'];
    }

    public function testCompatibleIpv6MembershipRetainsItsFamilyAcrossNativeFormatting(): void
    {
        $packed = \inet_pton('::203.0.115.7');
        self::assertIsString($packed);
        require_once __DIR__ . '/Fixtures/CidrNativeFormatProbe.php';
        Probe::$formats[bin2hex($packed)] = '::203.0.115.7';
        $network                          = Cidr::parse('::/96');
        self::assertTrue($network->contains('::203.0.115.7'));
        self::assertTrue($network->contains('::cb00:7307'));
        self::assertFalse($network->contains('203.0.115.7'));
        self::assertFalse($network->contains('::ffff:203.0.115.7'));
        self::assertSame(6, $network->family);
    }

    public function testMappedIpv6BecomesIpv4BeforeNativeFormatterAndMembership(): void
    {
        require_once __DIR__ . '/Fixtures/CidrNativeFormatProbe.php';
        self::assertSame('203.0.115.7', Cidr::address('::ffff:203.0.115.7'));
        self::assertSame(4, strlen(Probe::$calls[0]), 'Mapped addresses must reach native formatting as four bytes');
        $network = Cidr::parse('203.0.115.0/24');
        self::assertTrue($network->contains('::ffff:203.0.115.7'));
        self::assertFalse($network->contains('::ffff:203.0.116.7'));
        self::assertFalse($network->contains('::203.0.115.7'));
        self::assertSame(4, $network->family);
    }

    #[DataProvider('malformedMembershipCandidates')]
    public function testMalformedCandidatesReturnFalseBeforeAnyNativeFormatting(string $candidate): void
    {
        $network = Cidr::parse('2001:db8::/32');
        require_once __DIR__ . '/Fixtures/CidrNativeFormatProbe.php';
        self::assertFalse($network->contains($candidate));
        self::assertSame([], Probe::$calls);
    }

    /** @return iterable<string,array{string}> */
    public static function malformedMembershipCandidates(): iterable
    {
        yield 'empty' => [''];
        yield 'NUL suffix' => ["2001:db8::1\x00"];
        yield 'newline suffix' => ["2001:db8::1\n"];
        yield 'non-hex hextet' => ['2001:db8::gg'];
        yield 'too many IPv6 words' => ['1:2:3:4:5:6:7:8:9'];
        yield 'CIDR is not an address candidate' => ['2001:db8::/32'];
        yield 'leading zero IPv4 alias' => ['0203.0.115.7'];
    }

    #[DataProvider('realNativeAddresses')]
    public function testRealNativeCodecAndCanonicalAddressAgreeWithoutFormatterProbe(
        string $address,
        string $expected,
    ): void {
        $packed = \inet_pton($address);
        self::assertIsString($packed);
        $native = \inet_ntop($packed);
        self::assertIsString($native);
        self::assertSame($packed, \inet_pton($native));
        self::assertSame($expected, Cidr::address($address));
    }

    /** @return iterable<string,array{string,string}> */
    public static function realNativeAddresses(): iterable
    {
        yield 'ordinary IPv4' => ['203.0.115.7', '203.0.115.7'];
        yield 'ordinary IPv6' => ['2001:0DB8:0:0:0:0:0:1', '2001:db8::1'];
        yield 'IPv4-compatible IPv6' => ['::203.0.115.7', '::cb00:7307'];
        yield 'mapped IPv6' => ['::ffff:203.0.115.7', '203.0.115.7'];
    }
}
