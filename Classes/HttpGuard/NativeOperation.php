<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard;

use ErrorException;
use Throwable;

/** @internal Converts native warnings/notices into a fixed failure without leaking their text. */
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
        /** @var (callable(int, string, string, int): mixed)|null $previous */
        $previous = null;
        $handler  = static function (int $severity, string $message, string $file, int $line) use ($warning, &$previous): bool {
            if ($severity === E_WARNING || $severity === E_NOTICE) {
                throw $warning;
            }

            return $previous === null ? false : $previous($severity, $message, $file, $line) !== false;
        };
        $previous = set_error_handler($handler);
        try {
            return $operation();
        } catch (Throwable $failure) {
            if ($failure !== $warning && $failure->getPrevious() !== $warning) {
                throw $failure;
            }

            return false;
        } finally {
            restore_error_handler();
        }
    }
}
