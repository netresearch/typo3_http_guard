<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\DecisionEvent;
use Netresearch\HttpGuard\DecisionReporter;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\SystemClock;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use SensitiveParameter;

final class DecisionReporterSecretTest extends TestCase
{
    public function testOwnedKeyCanBeReleasedWithoutLosingMetricsOrLeakingOutsideCopies(): void
    {
        $rows     = [];
        $key      = 'synthetic lifecycle key';
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['hostHmacKeyEnv' => 'HTTP_GUARD_TEST_KEY']]),
            new SystemClock(),
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
            $key,
        );
        $reporter->report($this->event());
        self::assertSame(hash_hmac('sha256', 'api.example', $key), $rows[0]['host']);
        $reporter->clearHostHmacKey();
        $reporter->clearHostHmacKey();
        $reporter->report($this->event());
        self::assertArrayNotHasKey('host', $rows[1]);
        self::assertSame(2, array_sum(array_column($reporter->metrics(), 'count')));
        self::assertSame('synthetic lifecycle key', $key);
    }

    public function testDebugInformationAndConstructorTraceDoNotExposeKey(): void
    {
        $key      = 'synthetic debug secret';
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['hostHmacKeyEnv' => 'HTTP_GUARD_TEST_KEY']]),
            new SystemClock(),
            hostHmacKey: $key,
        );
        ob_start();
        try {
            var_dump($reporter);
            $dump = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        self::assertIsString($dump);
        self::assertStringNotContainsString($key, $dump);
        $parameter = (new ReflectionMethod(DecisionReporter::class, '__construct'))->getParameters()[3];
        self::assertCount(1, $parameter->getAttributes(SensitiveParameter::class));
        self::assertFalse((new ReflectionProperty(DecisionReporter::class, 'hostHmacKey'))->isReadOnly());
    }

    public function testDestructorReleasesOwnedKeyIdempotently(): void
    {
        $rows     = [];
        $reporter = new DecisionReporter(
            GuardConfig::fromArray(['logging' => ['hostHmacKeyEnv' => 'HTTP_GUARD_TEST_KEY']]),
            new SystemClock(),
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
            'synthetic destructor key',
        );
        $reporter->__destruct();
        $reporter->__destruct();
        $reporter->report($this->event());
        self::assertArrayNotHasKey('host', $rows[0]);
    }

    public function testUnusedKeyIsReleasedWhenHostLoggingCannotUseIt(): void
    {
        foreach ([[], ['logging' => ['hostMode' => 'plain', 'hostHmacKeyEnv' => 'HTTP_GUARD_TEST_KEY']]] as $data) {
            $reporter = new DecisionReporter(GuardConfig::fromArray($data), new SystemClock(), hostHmacKey: 'unused synthetic key');
            self::assertNull((new ReflectionProperty(DecisionReporter::class, 'hostHmacKey'))->getValue($reporter));
        }
    }

    private function event(): DecisionEvent
    {
        return new DecisionEvent(
            1,
            '',
            'enforce',
            'deny',
            'address_forbidden',
            null,
            '',
            'private_ipv4',
            'https',
            443,
            'static',
            str_repeat('b', 24),
            'api.example',
        );
    }
}
