<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Transport;

/** Loaded only in isolated PHPUnit processes; simulates missing native capabilities without changing the PHP installation. */
final class RuntimeCapabilityProbe
{
    public static array $overrides = [];

    public static function result(string $key, callable $fallback): mixed
    {
        return array_key_exists($key, self::$overrides) ? self::$overrides[$key] : $fallback();
    }
}

function extension_loaded(string $name): bool
{
    return RuntimeCapabilityProbe::result('extension:' . $name, static fn (): bool => \extension_loaded($name));
}

function function_exists(string $name): bool
{
    return RuntimeCapabilityProbe::result('function:' . $name, static fn (): bool => \function_exists($name));
}

function defined(string $name): bool
{
    return RuntimeCapabilityProbe::result('defined:' . $name, static fn (): bool => \defined($name));
}

function constant(string $name): mixed
{
    return RuntimeCapabilityProbe::result('constant:' . $name, static fn (): mixed => \constant($name));
}

function class_exists(string $name, bool $autoload = true): bool
{
    return RuntimeCapabilityProbe::result('class:' . $name, static fn (): bool => \class_exists($name, $autoload));
}

function curl_version(): array|false
{
    return RuntimeCapabilityProbe::result('curl_version', static fn (): array|false => \curl_version());
}
