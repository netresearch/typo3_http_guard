<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard;

use ErrorException;

/** @internal Converts a native warning into a fixed failure without leaking its text. */
final class NativeOperation
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T|false
     */
    public static function attempt(callable $operation): mixed
    {
        $warning = new ErrorException('Native operation failed', 0, E_WARNING);
        /** @var (callable(int, string, string, int): bool)|null $previous */
        $previous = null;
        $handler  = static function (int $severity, string $message, string $file, int $line) use ($warning, &$previous): bool {
            if ($severity === E_WARNING) {
                throw $warning;
            }

            return $previous === null ? false : $previous($severity, $message, $file, $line) !== false;
        };
        $previous = set_error_handler($handler);
        try {
            return $operation();
        } catch (ErrorException $failure) {
            if ($failure !== $warning) {
                throw $failure;
            }

            return false;
        } finally {
            restore_error_handler();
        }
    }
}
