<?php

declare (strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\NativeOperation;
use PHPUnit\Framework\TestCase;

final class NativeOperationTest extends TestCase
{
    public function testSuccessfulValuesArePreserved(): void
    {
        foreach ([false, 0, '', 'ok', null] as $value) {
            self::assertSame(
                $value,
                NativeOperation::attempt(static fn() => $value)
            );
        }
    }

    public function testInvalidNativeInputFailsWithoutDisclosingWarning(): void
    {
        $warnings = [];
        $handler = static function (
            int $severity,
            string $message
        ) use (&$warnings): bool {
            $warnings[] = [$severity, $message];
            return true;
        };
        set_error_handler($handler);
        try {
            self::assertFalse(
                NativeOperation::attempt(
                    static fn() => inet_pton('invalid-sensitive-host')
                )
            );
            self::assertSame([], $warnings);
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    public function testMissingFileFailsWithoutDisclosingPath(): void
    {
        $warnings = [];
        $handler = static function (
            int $severity,
            string $message
        ) use (&$warnings): bool {
            $warnings[] = [$severity, $message];
            return true;
        };
        set_error_handler($handler);
        try {
            self::assertFalse(
                NativeOperation::attempt(
                    static fn() => file_get_contents(
                        '/__http_guard_missing_private_path__/secret'
                    )
                )
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
                    }
                )
            );
            self::assertSame(
                [
                    [E_USER_NOTICE, 'expected user notice'],
                    [E_USER_WARNING, 'expected user warning'],
                ],
                $notices
            );
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    public function testUnrelatedExceptionPropagatesAndRestoresHandler(): void
    {
        $handler = static fn(): bool => true;
        set_error_handler($handler);
        $failure = new \ErrorException('caller exception', 0, E_WARNING);
        try {
            try {
                NativeOperation::attempt(
                    static function () use ($failure): never {
                        throw $failure;
                    }
                );
                self::fail('Caller exception was swallowed');
            } catch (\ErrorException $caught) {
                self::assertSame($failure, $caught);
            }
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    public function testNestedOperationsRestoreEachHandler(): void
    {
        $handler = static fn(): bool => true;
        set_error_handler($handler);
        try {
            self::assertSame(
                'outer',
                NativeOperation::attempt(
                    static function (): string {
                        self::assertFalse(
                            NativeOperation::attempt(
                                static fn() => inet_pton('invalid')
                            )
                        );
                        self::assertFalse(
                            NativeOperation::attempt(
                                static fn() => file_get_contents(
                                    '/__http_guard_missing__/secret'
                                )
                            )
                        );
                        return 'outer';
                    }
                )
            );
            $this->assertCurrentHandler($handler);
        } finally {
            restore_error_handler();
        }
    }

    /** @param callable(int, string, string, int): bool $handler */
    private function assertCurrentHandler(callable $handler): void
    {
        $current = set_error_handler(static fn(): bool => false);
        restore_error_handler();
        self::assertSame($handler, $current);
    }
}
