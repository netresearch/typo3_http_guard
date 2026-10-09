<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestFactoryCompatibilityTest extends TestCase
{
    #[DataProvider('coreVersions')]
    public function testSemanticCoreSupport(string $version, bool $supported): void
    {
        self::assertSame($supported, RequestFactoryCompatibility::supportsVersion($version));
    }

    public static function coreVersions(): iterable
    {
        yield 'current13' => ['13.4.36', true];
        yield 'later13patch' => ['13.4.37', true];
        yield 'later13minor' => ['13.5.0', true];
        yield 'current14' => ['14.3.8', true];
        yield 'later14patch' => ['14.3.9', true];
        yield 'later14minor' => ['14.4.0', true];
        yield 'tag' => ['v14.3.9', true];
        yield 'old13' => ['13.4.35', false];
        yield 'old14' => ['14.3.7', false];
        yield 'gap' => ['14.2.99', false];
        yield 'nextmajor' => ['15.0.0', false];
        yield 'previousmajor' => ['12.4.99', false];
        yield 'development' => ['dev-main', false];
        yield 'ambiguous' => ['14.3', false];
    }

    public function testCurrentGenuineParentPassesAbiValidation(): void
    {
        RequestFactoryCompatibility::assertSupported();
        self::assertContains(
            RequestFactoryCompatibility::replacementClass(),
            [
                \Netresearch\NrHttpGuard\Http\GuardedRequestFactory13::class,
                \Netresearch\NrHttpGuard\Http\GuardedRequestFactory14::class,
            ],
        );
    }

    #[DataProvider('parentReferenceShapes')]
    public function testParentReferenceDriftIsRejectedBeforeReplacementAutoload(
        string $coreMajor,
        bool $reference,
    ): void {
        // Controlled argv uses PHP_BINARY, a repository fixture and fixed validated provider values.
        // nosemgrep: php.lang.security.exec-use.exec-use
        $process = proc_open(
            [
                PHP_BINARY,
                dirname(__DIR__) . '/Fixtures/RequestFactoryReferenceShape.php',
                $coreMajor,
                $reference ? 'reference' : 'value',
            ],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame($reference ? 3 : 0, proc_close($process), (string) $stderr);
        self::assertSame($reference ? '' : 'SUPPORTED', $stdout);
        self::assertSame($reference ? 'transport_unsupported' : '', $stderr);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function parentReferenceShapes(): iterable
    {
        yield 'Core13 value return loads replacement' => ['13', false];
        yield 'Core13 reference return fails before class load' => ['13', true];
        yield 'Core14 readonly value return loads replacement' => ['14', false];
        yield 'Core14 readonly reference return fails before class load' => ['14', true];
    }
}
