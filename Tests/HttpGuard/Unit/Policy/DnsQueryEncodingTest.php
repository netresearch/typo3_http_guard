<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\DnsPacketCodec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DnsQueryEncodingTest extends TestCase
{
    #[DataProvider('queryParameters')]
    public function testQueryUsesExactHeaderQuestionAndSupportedRecordType(int $id, int $qtype): void
    {
        $expected = pack('nnnnnn', $id, 0x100, 1, 0, 0, 0) . "\x03api\x07example\x00" . pack('nn', $qtype, 1);
        self::assertSame($expected, (new DnsPacketCodec())->query('api.example.', $qtype, $id));
    }

    public function testMaximumDnsLabelIsEncodedWithoutTruncation(): void
    {
        $label    = str_repeat('a', 63);
        $expected = pack('nnnnnn', 4321, 0x100, 1, 0, 0, 0) . chr(63) . $label . "\x07example\x00" . pack('nn', 28, 1);
        self::assertSame($expected, (new DnsPacketCodec())->query($label . '.example.', 28, 4321));
    }

    /** @return iterable<string, array{int, int}> */
    public static function queryParameters(): iterable
    {
        foreach ([0, 65535] as $id) {
            foreach ([1, 28, 5] as $qtype) {
                yield 'id-' . $id . '-type-' . $qtype => [$id, $qtype];
            }
        }
    }
}
