<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{DnsPacketCodec, PolicyException};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DnsCodecMutationBoundaryTest extends TestCase
{
    #[DataProvider('reservedLabelTags')]
    public function testReservedLabelTagCannotBecomeAnAnswerOwner(string $owner): void
    {
        $question = "\x03api\x07example\x00" . pack('nn', 1, 1);
        $packet   = pack('nnnnnn', 7, 0x8100, 1, 1, 0, 0) . $question . $owner . pack('nnNn', 1, 1, 5, 4) . "\x08\x08\x08\x08";
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('resolution_unverified');
        (new DnsPacketCodec())->decode($packet, 7, 'api.example.', 1);
    }

    public static function reservedLabelTags(): iterable
    {
        yield 'extended label tag' => ['@' . str_repeat('a', 64) . "\x00"];
        yield 'reserved label tag' => ["\x80" . str_repeat('a', 128) . "\x00"];
    }
}
