<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\DnsPacketCodec;
use Netresearch\HttpGuard\PolicyException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DnsResidualWireContractTest extends TestCase
{
    private const QUESTION = "\x03api\x07example\x00\x00\x01\x00\x01";

    public function testOneCharacterLabelEncodesAsAnOrdinaryAbsoluteQuestion(): void
    {
        self::assertSame(
            pack('nnnnnn', 65535, 0x100, 1, 0, 0, 0) . "\x01a\x00" . pack('nn', 28, 1),
            (new DnsPacketCodec())->query('a.', 28, 65535),
        );
    }

    public function testExpectedQuestionCaseDoesNotChangeDnsNameIdentity(): void
    {
        $packet = pack('nnnnnn', 7, 0x8100, 1, 0, 0, 0) . self::QUESTION;
        self::assertSame(
            ['records' => [], 'truncated' => false],
            (new DnsPacketCodec())->decode($packet, 7, 'API.EXAMPLE.', 1),
        );
    }

    #[DataProvider('incompletePackets')]
    public function testIncompleteWireFieldsHaveOnlyControlledPolicyFailure(string $packet): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1);
    }

    public static function incompletePackets(): iterable
    {
        $header = pack('nnnnnn', 7, 0x8100, 1, 0, 0, 0);
        foreach ([0, 1, 11, 12] as $size) {
            yield 'incomplete header or absent name ' . $size => [substr($header, 0, $size)];
        }
        $name = substr(self::QUESTION, 0, -4);
        foreach ([0, 1, 2, 3] as $size) {
            yield 'incomplete question trailer ' . $size => [$header . $name . substr(pack('nn', 1, 1), 0, $size)];
        }
        $answer = pack('nnNn', 16, 1, 5, 2);
        $prefix = pack('nnnnnn', 7, 0x8100, 1, 1, 0, 0) . self::QUESTION . "\xc0\f";
        foreach ([0, 1, 8, 9] as $size) {
            yield 'incomplete record header ' . $size => [$prefix . substr($answer, 0, $size)];
        }
        yield 'unknown record short rdata' => [$prefix . $answer . 'x'];
        yield 'name label extends beyond packet' => [$header . '?abc'];
        yield 'answer pointer lacks second byte' => [pack('nnnnnn', 7, 0x8100, 1, 1, 0, 0) . self::QUESTION . "\xc0"];
    }

    public function testMaximumLegalCompressionTraversalRetainsCompleteEmptyResponse(): void
    {
        $count    = 126;
        $packet   = pack('nnnnnn', 7, 0x8100, 1, $count, 0, 0) . self::QUESTION;
        $previous = 12;
        for ($n = 0; $n < $count; ++$n) {
            $owner = strlen($packet);
            $packet .= pack('n', 0xC000 | $previous) . pack('nnNn', 16, 1, 5, 0);
            $previous = $owner;
        }
        self::assertSame(
            ['records' => [], 'truncated' => false],
            (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1),
        );
    }
}
