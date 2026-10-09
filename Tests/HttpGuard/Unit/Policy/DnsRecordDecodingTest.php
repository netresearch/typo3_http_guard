<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\DnsPacketCodec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DnsRecordDecodingTest extends TestCase
{
    #[DataProvider('recordTypesAndTtls')]
    public function testNormalAnswerPreservesItsTypeAddressAndTtl(
        int $qtype,
        string $type,
        string $field,
        string $value,
        string $bytes,
        int $ttl,
    ): void {
        $question = "\x03api\x07example\x00" . pack('nn', $qtype, 1);
        $record   = "\xc0\f" . pack('nnNn', $qtype, 1, $ttl, strlen($bytes)) . $bytes;
        $packet   = pack('nnnnnn', 4321, 0x8180, 1, 1, 0, 0) . $question . $record;
        self::assertSame(
            [
                'records'   => [['host' => 'api.example', 'type' => $type, 'ttl' => $ttl, $field => $value]],
                'truncated' => false,
            ],
            (new DnsPacketCodec())->decode($packet, 4321, 'api.example.', $qtype),
        );
    }

    public function testAuthorityAndAdditionalAddressesAreNotReturnedAsAnswers(): void
    {
        $question   = "\x03api\x07example\x00" . pack('nn', 1, 1);
        $answer     = "\xc0\f" . pack('nnNn', 1, 1, 60, 4) . "\x08\x08\x08\x08";
        $authority  = "\xc0\f" . pack('nnNn', 1, 1, 60, 4) . "\x08\x08\x04\x04";
        $additional = "\xc0\f" . pack('nnNn', 28, 1, 60, 16) . pack('n8', 0x2001, 0x4860, 0, 0, 0, 0, 0, 0x8888);
        $packet     = pack('nnnnnn', 4321, 0x8180, 1, 1, 1, 1) . $question . $answer . $authority . $additional;
        self::assertSame(
            [
                'records'   => [['host' => 'api.example', 'type' => 'A', 'ttl' => 60, 'ip' => '8.8.8.8']],
                'truncated' => false,
            ],
            (new DnsPacketCodec())->decode($packet, 4321, 'api.example.', 1),
        );
    }

    /** @return iterable<string, array{int, string, string, string, string, int}> */
    public static function recordTypesAndTtls(): iterable
    {
        foreach ([0, 60, 2147483648] as $ttl) {
            yield 'A-ttl-' . $ttl => [1, 'A', 'ip', '8.8.8.8', "\x08\x08\x08\x08", $ttl];
            yield 'AAAA-ttl-' . $ttl => [28, 'AAAA', 'ipv6', '2001:4860::8888', pack('n8', 0x2001, 0x4860, 0, 0, 0, 0, 0, 0x8888), $ttl];
            yield 'CNAME-ttl-' . $ttl => [5, 'CNAME', 'target', 'alias.example', "\x05alias\x07example\x00", $ttl];
        }
    }
}
