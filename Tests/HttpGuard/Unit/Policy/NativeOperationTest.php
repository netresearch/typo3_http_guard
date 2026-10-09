<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use ErrorException;
use Netresearch\HttpGuard\NativeOperation;
use PHPUnit\Framework\TestCase;
use ValueError;

final class NativeOperationTest extends TestCase
{
    public function testSuccessfulValuesArePreserved(): void
    {
        foreach ([false, 0, '', 'ok', null] as $value) {
            self::assertSame($value, NativeOperation::attempt(static fn () => $value));
        }
    }

    public function testInvalidNativeInputFailsWithoutDisclosingWarning(): void
    {
        $warnings = [];
        $handler  = static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        };
        set_error_handler($handler);
        try {
            self::assertFalse(NativeOperation::attempt(static fn () => inet_pton('invalid-sensitive-host')));
            self::assertSame([], $warnings);
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    public function testMissingFileFailsWithoutDisclosingPath(): void
    {
        $warnings = [];
        $handler  = static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        };
        set_error_handler($handler);
        try {
            self::assertFalse(
                NativeOperation::attempt(
                    static fn () => file_get_contents('/__http_guard_missing_private_path__/secret'),
                ),
            );
            self::assertSame([], $warnings);
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    public function testNonNativeWarningStillReachesPreviousHandler(): void
    {
        $notices = [];
        $handler = static function (int $severity, string $message) use (&$notices): bool {
            $notices[] = [$severity, $message];

            return true;
        };
        set_error_handler($handler);
        try {
            self::assertSame(
                'continued',
                NativeOperation::attempt(
                    static function (): string {
                        trigger_error('expected user notice', E_USER_NOTICE);
                        trigger_error('expected user warning', E_USER_WARNING);

                        return 'continued';
                    },
                ),
            );
            self::assertSame(
                [[E_USER_NOTICE, 'expected user notice'], [E_USER_WARNING, 'expected user warning']],
                $notices,
            );
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    public function testUnrelatedExceptionPropagatesAndRestoresHandler(): void
    {
        $handler = static fn (): bool => true;
        set_error_handler($handler);
        $failure = new ErrorException('caller exception', 0, E_WARNING);
        try {
            try {
                NativeOperation::attempt(
                    static function () use ($failure): never {
                        throw $failure;
                    },
                );
                self::fail('Caller exception was swallowed');
            } catch (ErrorException $caught) {
                self::assertSame($failure, $caught);
            }
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    public function testNestedOperationsRestoreEachHandler(): void
    {
        $handler = static fn (): bool => true;
        set_error_handler($handler);
        try {
            self::assertSame(
                'outer',
                NativeOperation::attempt(
                    static function (): string {
                        self::assertFalse(NativeOperation::attempt(static fn () => inet_pton('invalid')));
                        self::assertFalse(
                            NativeOperation::attempt(static fn () => file_get_contents('/__http_guard_missing__/secret')),
                        );

                        return 'outer';
                    },
                ),
            );
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    /** @param callable(int,string,string,int):mixed $handler */
    private function assertCurrentHandler(callable $handler): void
    {
        $current = set_error_handler(static fn (): bool => false);
        restore_error_handler();
        self::assertSame($handler, $current);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nativeFailures')]
    public function testNativeReadWriteAndSelectFailuresHideDetailsAndRestoreHandler(string $operation): void
    {
        $path = tempnam(sys_get_temp_dir(), 'http-guard-native-');
        self::assertIsString($path);
        $readOnly  = fopen($path, 'rb');
        $writeOnly = fopen($path, 'wb');
        $memory    = fopen('php://memory', 'r+');
        self::assertIsResource($readOnly);
        self::assertIsResource($writeOnly);
        self::assertIsResource($memory);
        $warnings = [];
        $handler  = static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        };
        set_error_handler($handler);
        try {
            $call = match ($operation) {
                'write'  => static fn () => fwrite($readOnly, 'private native data'),
                'read'   => static fn () => fread($writeOnly, 1),
                'select' => static function () use ($memory): int|false {
                    $read   = [$memory];
                    $write  = [];
                    $except = [];

                    return stream_select($read, $write, $except, 0);
                },
            };
            self::assertFalse(NativeOperation::attempt($call));
            self::assertSame([], $warnings);
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
            fclose($readOnly);
            fclose($writeOnly);
            fclose($memory);
            unlink($path);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function nativeFailures(): iterable
    {
        yield 'native fwrite notice' => ['write'];
        yield 'native fread notice' => ['read'];
        yield 'native stream_select warning' => ['select'];
    }

    public function testUnrelatedValueErrorStillPropagatesAndRestoresHandler(): void
    {
        $handler = static fn (): bool => true;
        $failure = new ValueError('caller-controlled sensitive error');
        set_error_handler($handler);
        try {
            try {
                NativeOperation::attempt(
                    static function () use ($failure): never {
                        throw $failure;
                    },
                );
                self::fail('Caller ValueError swallowed');
            } catch (ValueError $caught) {
                self::assertSame($failure, $caught);
            }
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonBooleanPreviousResults')]
    public function testPreviousHandlersMayReturnNonBooleanHandledValues(mixed $value): void
    {
        $seen     = [];
        $previous = static function (int $severity, string $message) use ($value, &$seen): mixed {
            $seen[] = [$severity, $message];

            return $value;
        };
        set_error_handler($previous);
        try {
            self::assertSame(
                'continued',
                NativeOperation::attempt(
                    static function (): string {
                        trigger_error('synthetic nonboolean previous result', E_USER_NOTICE);

                        return 'continued';
                    },
                ),
            );
            self::assertSame([[E_USER_NOTICE, 'synthetic nonboolean previous result']], $seen);
            $this->assertCurrentHandler($previous);
        } finally {
            restore_error_handler();
        }
    }

    /** @return iterable<string, array{mixed}> */
    public static function nonBooleanPreviousResults(): iterable
    {
        yield 'integer zero is handled' => [0];
        yield 'null is handled' => [null];
        yield 'string is handled' => ['handled'];
    }
}
