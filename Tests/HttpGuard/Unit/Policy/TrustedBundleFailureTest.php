<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\PolicyException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TrustedBundleFailureTest extends TestCase
{
    #[DataProvider('bundleFailures')]
    public function testTrustedBundleFailuresAreFixedAndRestoreHandler(string $case): void
    {
        // Fixed PHP executable and committed fixture argv; the case is drawn from this provider.
        // nosemgrep: php.lang.security.exec-use.exec-use
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/Fixtures/TrustedBundleFailure.php', $case],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), (string) $stderr);
        self::assertIsString($stdout);
        $result = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['unsupported'])) {
            self::markTestSkipped($result['unsupported']);
        }
        self::assertSame(PolicyException::class, $result['class'] ?? null);
        self::assertSame('configuration_invalid', $result['reason']);
        self::assertSame('Outbound HTTP policy: configuration_invalid', $result['message']);
        self::assertTrue($result['handlerRestored']);
        self::assertSame([], $result['warnings']);
        self::assertSame('', $stderr);
        self::assertTrue($result['sourceIsolated']);
    }

    /** @return iterable<string, array{string}> */
    public static function bundleFailures(): iterable
    {
        foreach ([
            'hash_missing',
            'hash_unreadable',
            'classifier_missing',
            'classifier_unreadable',
            'classifier_corrupt',
            'classifier_wrong_shape',
            'classifier_bad_rule',
            'classifier_empty_rules',
        ] as $case) {
            yield $case => [$case];
        }
    }
}
