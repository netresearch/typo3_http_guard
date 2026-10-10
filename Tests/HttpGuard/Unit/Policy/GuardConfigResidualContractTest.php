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

final class GuardConfigResidualContractTest extends TestCase
{
    #[DataProvider('invalidStaticBindings')]
    public function testMalformedStaticBindingHasControlledConfigurationFailure(string $host, string $address): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        GuardConfig::fromArray(['resolver' => ['staticHosts' => [$host => [$address]]]]);
    }

    public static function invalidStaticBindings(): iterable
    {
        yield 'host contains path' => ['api.example/path', '8.8.8.8'];
        yield 'host contains disallowed underscore' => ['bad_host.example', '8.8.8.8'];
        yield 'host has empty label' => ['api..example', '8.8.8.8'];
        yield 'address not IP' => ['api.example', 'not an IP'];
        yield 'address overrange octet' => ['api.example', '999.8.8.8'];
        yield 'address truncated v6' => ['api.example', '2001:'];
        yield 'address prefix is not a literal' => ['api.example', '8.8.8.8/32'];
    }

    public function testDuplicateCanonicalStaticAddressesAreDenseAndSortedAfterNormalization(): void
    {
        $config = GuardConfig::fromArray(
            [
                'resolver' => [
                    'staticHosts' => ['API.EXAMPLE.' => ['::ffff:8.8.8.8', '8.8.8.8', '1.1.1.1', '2606:4700:4700:0:0:0:0:1111']],
                ],
            ],
        );
        self::assertSame(
            ['api.example' => ['1.1.1.1', '2606:4700:4700::1111', '8.8.8.8']],
            $config->data['resolver']['staticHosts'],
        );
        self::assertTrue(array_is_list($config->data['resolver']['staticHosts']['api.example']));
    }
}
