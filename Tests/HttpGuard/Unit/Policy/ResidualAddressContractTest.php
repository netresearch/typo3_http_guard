<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\Cidr;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\AddressRulesBoundaryState as State;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class ResidualAddressContractTest extends TestCase
{
    #[DataProvider('classifications')]
    public function testClassificationCarriesAllEndpointPermissionFlags(
        string $ip,
        string $class,
        bool $public,
        bool $exceptable,
        bool $denied,
    ): void {
        $result = (new AddressClassifier())->classify($ip);
        self::assertSame($class, $result->addressClass);
        self::assertSame($public, $result->public);
        self::assertSame($exceptable, $result->endpointExceptable);
        self::assertSame($denied, $result->hardDenied);
    }

    /** @return iterable<string,array{string,string,bool,bool,bool}> */
    public static function classifications(): iterable
    {
        yield 'ordinary IPv4' => ['8.8.8.8', 'ordinary_global_unicast', true, true, false];
        yield 'ordinary IPv6' => ['2606:4700:4700::1111', 'ordinary_global_unicast', true, true, false];
        yield 'unallocated IPv6' => ['4000::1', 'unallocated_or_non_global_ipv6', false, false, true];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testMostSpecificTrustedIpv4RuleWinsRegardlessOfInputOrder(): void
    {
        $data = json_decode(
            file_get_contents(
                dirname(__DIR__, 4) . '/Resources/Private/HttpGuard/data/security-corpus/address-rules.json',
            ),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $data['iana_special_rules'] = [
            ['cidr' => '10.0.0.0/8', 'class' => 'private_ipv4', 'endpoint' => 'requires_bound_endpoint_and_narrow_cidr'],
            ['cidr' => '10.0.0.0/16', 'class' => 'narrow_forbidden', 'endpoint' => 'forbidden'],
        ];
        require_once __DIR__ . '/Fixtures/AddressRulesBoundaryShim.php';
        State::$payload = json_encode($data, JSON_THROW_ON_ERROR);
        $classifier     = new AddressClassifier();
        $narrow         = $classifier->classify('10.0.7.1');
        self::assertSame('narrow_forbidden', $narrow->addressClass);
        self::assertTrue($narrow->hardDenied);
        self::assertFalse($narrow->endpointExceptable);
        $broad = $classifier->classify('10.1.7.1');
        self::assertSame('private_ipv4', $broad->addressClass);
        self::assertFalse($broad->hardDenied);
        self::assertTrue($broad->endpointExceptable);
    }

    #[DataProvider('prefixedNetworks')]
    public function testCidrParserRejectsAValidNetworkHiddenBehindAForbiddenPrefix(string $input): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        Cidr::parse($input);
    }

    /** @return iterable<string,array{string}> */
    public static function prefixedNetworks(): iterable
    {
        yield 'word before IPv4' => ['junk10.0.0.0/8'];
        yield 'punctuation before IPv6' => ['_2001:db8::/32'];
        yield 'slash before IPv4' => ['/10.0.0.0/8'];
    }
}
