<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GuardConfigurationOriginAndDateTest extends TestCase
{
    #[DataProvider('invalidProfiles')]
    public function testProfileOriginAndExpiryHaveControlledStrictFailures(array $overrides): void
    {
        $profile = array_replace(
            [
                'origin'       => 'https://erp.example',
                'allowedCidrs' => ['10.23.4.12/32'],
                'methods'      => ['GET'],
                'purpose'      => 'Offline configuration boundary',
                'owner'        => 'Test maintainers',
            ],
            $overrides,
        );
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        GuardConfig::fromArray(['endpoints' => ['erp' => $profile]]);
    }

    /** @return iterable<string,array{array<string,mixed>}> */
    public static function invalidProfiles(): iterable
    {
        yield 'complete origin cannot be found inside another URL path' => [['origin' => 'https://evil.example/path/https://erp.example']];
        yield 'negative timezone hour exceeds the bound' => [['expiresAt' => '2030-12-31T23:59:59-24:00']];
        yield 'positive timezone hour exceeds the bound' => [['expiresAt' => '2030-12-31T23:59:59+24:00']];
    }

    #[DataProvider('malformedLiteralCidrs')]
    public function testMalformedLiteralCidrsDoNotLeakNativeTypeErrors(string $cidr): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        GuardConfig::fromArray(['deniedCidrs' => [$cidr]]);
    }

    /** @return iterable<string,array{string}> */
    public static function malformedLiteralCidrs(): iterable
    {
        yield 'truncated IPv6' => ['2001:/32'];
        yield 'incomplete IPv4' => ['1.2.3/24'];
        yield 'bare hexadecimal text' => ['ffff/32'];
    }
}
