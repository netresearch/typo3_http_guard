<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\HandlerStack;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\Transport\InvocationRegistry;
use PHPUnit\Framework\TestCase;

final class InvocationLifecycleTest extends TestCase
{
    public function testOpeningAndClosingAnInvocationPreservesItsExactContext(): void
    {
        $context  = (new PolicyRegistry(GuardConfig::fromArray([]), new SystemClock()))->newContext();
        $registry = new InvocationRegistry();
        $stack    = new HandlerStack();
        self::assertSame(0, $registry->activeCount());
        [$envelope, $token] = $registry->open($context, 'https://api.example', 'https', $stack, true);
        self::assertSame(1, $registry->activeCount());
        self::assertSame('https://api.example', $envelope->origin);
        self::assertSame('https', $envelope->scheme);
        self::assertSame($stack, $envelope->expectedHandler);
        self::assertTrue($envelope->publicFetch);
        self::assertSame($envelope, $registry->assert($envelope, $token, $context, $context));
        $registry->close($envelope);
        self::assertSame(0, $registry->activeCount());
        $registry->close($envelope);
        self::assertSame(0, $registry->activeCount());
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('grant_invalid');
        $registry->assert($envelope, $token, $context, $context);
    }

    public function testSeparateInvocationsCanBeClosedIndependently(): void
    {
        $context                = (new PolicyRegistry(GuardConfig::fromArray([]), new SystemClock()))->newContext();
        $registry               = new InvocationRegistry();
        [$first]                = $registry->open($context, 'https://first.example', 'https', null);
        [$second, $secondToken] = $registry->open($context, 'http://second.example', 'http', null);
        self::assertNotSame($first, $second);
        self::assertFalse($second->publicFetch);
        self::assertNull($second->expectedHandler);
        self::assertSame(2, $registry->activeCount());
        $registry->close($first);
        self::assertSame(1, $registry->activeCount());
        self::assertSame($second, $registry->assert($second, $secondToken, $context, $context));
        $registry->close($second);
        self::assertSame(0, $registry->activeCount());
    }

    public function testUnreferencedEnvelopeDoesNotLeaveAnActiveInvocation(): void
    {
        $context            = (new PolicyRegistry(GuardConfig::fromArray([]), new SystemClock()))->newContext();
        $registry           = new InvocationRegistry();
        [$envelope, $token] = $registry->open($context, 'https://api.example', 'https', null);
        self::assertSame(1, $registry->activeCount());
        unset($envelope, $token);
        gc_collect_cycles();
        self::assertSame(0, $registry->activeCount());
    }
}
