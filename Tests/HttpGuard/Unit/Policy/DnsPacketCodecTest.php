<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{DnsPacketCodec, PolicyException};
use PHPUnit\Framework\TestCase;

final class DnsPacketCodecTest extends TestCase
{
    private function packet(int $flags = 0x8180): string
    {
        $question = "\x03api\x07example\x00" . pack('nn', 1, 1);
        $a        = "\xc0\f" . pack('nnNn', 1, 1, 60, 4) . inet_pton('8.8.8.8');

        return pack('nnnnnn', 1234, $flags, 1, 1, 0, 0) . $question . $a;
    }

    public function testExactQuestionAndCompressedAddressDecode(): void
    {
        $decoded = (new DnsPacketCodec())->decode($this->packet(), 1234, 'api.example.', 1);
        self::assertFalse($decoded['truncated']);
        self::assertSame(
            [['host' => 'api.example', 'type' => 'A', 'ttl' => 60, 'ip' => '8.8.8.8']],
            $decoded['records'],
        );
    }

    public function testTruncatedResponseNeverReturnsPartialAddresses(): void
    {
        $decoded = (new DnsPacketCodec())->decode($this->packet(0x8380), 1234, 'api.example.', 1);
        self::assertTrue($decoded['truncated']);
        self::assertSame([], $decoded['records']);
    }

    public function testWrongTransactionQuestionRcodeAndPacketBoundsFailClosed(): void
    {
        $codec = new DnsPacketCodec();
        foreach ([
            [$this->packet(), 2222, 'api.example.', 1],
            [$this->packet(), 1234, 'wrong.example.', 1],
            [$this->packet(0x8183), 1234, 'api.example.', 1],
            [substr($this->packet(), 0, -1), 1234, 'api.example.', 1],
            [$this->packet() . 'extra', 1234, 'api.example.', 1],
        ] as $args) {
            try {
                $codec->decode(...$args);
                self::fail('Invalid DNS packet accepted');
            } catch (PolicyException $e) {
                self::assertSame('resolution_unverified', $e->reasonCode());
            }
        }
    }

    public function testCompressionPointerCycleAndUnsupportedLabelTagsAreRejected(): void
    {
        $header = pack('nnnnnn', 1234, 0x8180, 1, 0, 0, 0);
        foreach (["\xc0\f", "@api\x00"] as $name) {
            try {
                (new DnsPacketCodec())->decode($header . $name . pack('nn', 1, 1), 1234, 'api.example.', 1);
                self::fail('Bad pointer accepted');
            } catch (PolicyException $e) {
                self::assertSame('resolution_unverified', $e->reasonCode());
            }
        }
    }

    public function testAuthorityAndAdditionalAddressesCannotBecomeTargetCandidates(): void
    {
        $question = "\x03api\x07example\x00" . pack('nn', 1, 1);
        $a        = "\xc0\f" . pack('nnNn', 1, 1, 60, 4) . inet_pton('10.23.4.12');
        $packet   = pack('nnnnnn', 1234, 0x8180, 1, 0, 0, 1) . $question . $a;
        self::assertSame([], (new DnsPacketCodec())->decode($packet, 1234, 'api.example.', 1)['records']);
    }
}
