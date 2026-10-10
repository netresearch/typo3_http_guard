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

final class DnsWireBoundaryContractTest extends TestCase
{
    private const QUESTION = "\x03api\x07example\x00\x00\x01\x00\x01";

    #[DataProvider('invalidQueries')]
    public function testQueryRequiresCanonicalAbsoluteDnsQuestion(string $name, int $type, int $id): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new DnsPacketCodec())->query($name, $type, $id);
    }

    public static function invalidQueries(): iterable
    {
        foreach (['api.example', 'API.example.', 'api.example..', '.', '[::1].', 'api_.example.'] as $name) {
            yield $name => [$name, 1, 7];
        }
        foreach ([0, 2, 39, 65535] as $type) {
            yield 'type ' . $type => ['api.example.', $type, 7];
        }
        yield 'negative id' => ['api.example.', 1, -1];
        yield 'id overflow' => ['api.example.', 1, 65536];
    }

    #[DataProvider('invalidHeaders')]
    public function testMismatchedResponseHeaderOrQuestionIsRejected(
        int $flags,
        int $questions,
        int $type,
        int $class,
    ): void {
        $packet = pack('nnnnnn', 7, $flags, $questions, 0, 0, 0) . substr(self::QUESTION, 0, -4) . pack('nn', $type, $class);
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1);
    }

    public static function invalidHeaders(): iterable
    {
        yield 'not a response' => [0x100, 1, 1, 1];
        yield 'nonstandard opcode' => [0x8900, 1, 1, 1];
        foreach ([1, 2, 3, 5, 15] as $rcode) {
            yield 'rcode ' . $rcode => [0x8100 | $rcode, 1, 1, 1];
        }
        yield 'zero questions' => [0x8100, 0, 1, 1];
        yield 'two questions' => [0x8100, 2, 1, 1];
        yield 'wrong type' => [0x8100, 1, 28, 1];
        yield 'wrong class' => [0x8100, 1, 1, 3];
    }

    #[DataProvider('invalidRecords')]
    public function testKnownAnswerShapeIsRequired(int $type, int $class, string $data): void
    {
        $packet = pack('nnnnnn', 7, 0x8100, 1, 1, 0, 0) . self::QUESTION . "\xc0\f" . pack('nnNn', $type, $class, 5, strlen($data)) . $data;
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1);
    }

    public static function invalidRecords(): iterable
    {
        yield 'A not IN' => [1, 3, "\x08\x08\x08\x08"];
        yield 'AAAA not IN' => [28, 3, str_repeat(chr(0), 16)];
        yield 'CNAME not IN' => [5, 3, "\xc0\f"];
        foreach ([0, 3, 5] as $length) {
            yield 'A length ' . $length => [1, 1, str_repeat(chr(8), $length)];
        }
        foreach ([0, 15, 17] as $length) {
            yield 'AAAA length ' . $length => [28, 1, str_repeat(chr(0), $length)];
        }
        yield 'CNAME trailing byte' => [5, 1, "\xc0\f\x00"];
        yield 'DNAME answer' => [39, 1, "\xc0\f"];
    }

    public function testExactly4096UnknownRecordsAreBoundedAndDoNotInventAddresses(): void
    {
        $record = "\xc0\f" . pack('nnNn', 16, 1, 5, 0);
        $packet = pack('nnnnnn', 7, 0x8100, 1, 4096, 0, 0) . self::QUESTION . str_repeat($record, 4096);
        self::assertSame(
            ['records' => [], 'truncated' => false],
            (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1),
        );
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_limit');
        (new DnsPacketCodec())->decode(pack('nnnnnn', 7, 0x8100, 1, 4097, 0, 0) . self::QUESTION, 7, 'api.example.', 1);
    }

    public function testExpandedMaximumNameAndUppercaseWireLabelsAreCanonicalized(): void
    {
        $name   = str_repeat('a', 63) . '.' . str_repeat('b', 63) . '.' . str_repeat('c', 63) . '.' . str_repeat('d', 61);
        $codec  = new DnsPacketCodec();
        $query  = $codec->query($name . '.', 1, 7);
        $packet = pack('nnnnnn', 7, 0x8100, 1, 1, 0, 0) . substr($query, 12) . "\xc0\f" . pack('nnNn', 1, 1, 0, 4) . "\xcb\x00s\x07";
        self::assertSame($name, $codec->decode($packet, 7, $name . '.', 1)['records'][0]['host']);
        $upper = pack('nnnnnn', 7, 0x8100, 1, 0, 0, 0) . strtoupper(self::QUESTION);
        self::assertSame(['records' => [], 'truncated' => false], $codec->decode($upper, 7, 'api.example.', 1));
    }

    #[DataProvider('badNames')]
    public function testMalformedCompressedNameIsRejected(string $wire): void
    {
        $packet = pack('nnnnnn', 7, 0x8100, 1, 0, 0, 0) . $wire . pack('nn', 1, 1);
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1);
    }

    public static function badNames(): iterable
    {
        yield 'pointer to header' => ["\xc0\x00"];
        yield 'forward pointer' => ["\xc0\x0e"];
        yield 'truncated pointer' => ["\xc0"];
        yield 'extended label tag' => ['@'];
        yield 'reserved label tag' => ["\x80"];
        yield 'invalid label octet' => ["\x03a/b\x00"];
        yield 'expanded 256 octets' => [
            '?' . str_repeat('a', 63) . '?' . str_repeat('b', 63) . '?' . str_repeat('c', 63) . '>' . str_repeat('d', 62) . "\x00",
        ];
    }

    public function testMaximumDnsPacketFitsLengthPrefixAndLargerPacketIsRejected(): void
    {
        $prefix        = pack('nnnnnn', 7, 0x8100, 1, 1, 0, 0) . self::QUESTION . "\xc0\f";
        $payloadLength = 65535 - strlen($prefix) - 10;
        $packet        = $prefix . pack('nnNn', 16, 1, 5, $payloadLength) . str_repeat('x', $payloadLength);
        self::assertSame(
            ['records' => [], 'truncated' => false],
            (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1),
        );
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new DnsPacketCodec())->decode(
            $prefix . pack('nnNn', 16, 1, 5, $payloadLength + 1) . str_repeat('x', $payloadLength + 1),
            7,
            'api.example.',
            1,
        );
    }

    #[DataProvider('invalidAnswerOwners')]
    public function testMalformedAnswerOwnerCannotBeHiddenByAValidQuestion(string $owner): void
    {
        $packet = pack('nnnnnn', 7, 0x8100, 1, 1, 0, 0) . self::QUESTION . $owner . pack('nnNn', 1, 1, 5, 4) . "\xcb\x00s\x07";
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1);
    }

    public static function invalidAnswerOwners(): iterable
    {
        yield 'header pointer' => ["\xc0\x00"];
        yield 'invalid label octet' => ["\x03a/b\x00"];
        yield 'expanded 256 octets' => [
            '?' . str_repeat('a', 63) . '?' . str_repeat('b', 63) . '?' . str_repeat('c', 63) . '>' . str_repeat('d', 62) . "\x00",
        ];
    }

    public function testHighOffsetAndNestedCompressionPointersPreserveOwnerAndRdataBoundaries(): void
    {
        $packet = pack('nnnnnn', 7, 0x8100, 1, 3, 0, 0) . self::QUESTION;
        $packet .= "\xc0\f" . pack('nnNn', 16, 1, 5, 300) . str_repeat('x', 300);
        $pointer = pack('n', 0xC000 | strlen($packet));
        $packet .= "\xc0\f" . pack('nnNn', 1, 1, 5, 4) . "\xcb\x00s\x07";
        $packet .= $pointer . pack('nnNn', 5, 1, 5, 2) . $pointer;
        self::assertSame(
            [
                ['host' => 'api.example', 'type' => 'A', 'ttl' => 5, 'ip' => '203.0.115.7'],
                ['host' => 'api.example', 'type' => 'CNAME', 'ttl' => 5, 'target' => 'api.example'],
            ],
            (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1)['records'],
        );
    }

    public function testCompressionChainTraversalIsBoundedWhileShortLegalChainWorks(): void
    {
        foreach ([120, 140] as $count) {
            $packet   = pack('nnnnnn', 7, 0x8100, 1, $count, 0, 0) . self::QUESTION;
            $previous = 12;
            for ($n = 0; $n < $count; ++$n) {
                $ownerOffset = strlen($packet);
                $packet .= pack('n', 0xC000 | $previous) . pack('nnNn', 16, 1, 5, 0);
                $previous = $ownerOffset;
            }
            if ($count === 120) {
                self::assertSame([], (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1)['records']);
            } else {
                try {
                    (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1);
                    self::fail('Unbounded compression traversal accepted');
                } catch (PolicyException $exception) {
                    self::assertSame('resolution_unverified', $exception->reasonCode());
                }
            }
        }
    }

    public function testRecordLimitIncludesAuthorityAndAdditionalSections(): void
    {
        foreach ([[0, 4097, 0], [0, 0, 4097], [1, 2048, 2048]] as [$answers, $authority, $additional]) {
            try {
                (new DnsPacketCodec())->decode(
                    pack('nnnnnn', 7, 0x8100, 1, $answers, $authority, $additional) . self::QUESTION,
                    7,
                    'api.example.',
                    1,
                );
                self::fail('Section-wide record limit lost');
            } catch (PolicyException $exception) {
                self::assertSame('resolution_limit', $exception->reasonCode());
            }
        }
    }
}
