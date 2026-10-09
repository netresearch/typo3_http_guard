<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\GuardConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GuardConfigProfileContractTest extends TestCase
{
    public function testStaticHostsCanonicalizeDeduplicateAndSort(): void
    {
        $config = GuardConfig::fromArray(
            [
                'resolver' => [
                    'staticHosts' => [
                        'Zeta.Example' => ['192.0.2.9'],
                        'API.Example'  => ['192.0.2.8', '2001:0DB8::1', '::ffff:192.0.2.8', '192.0.2.3'],
                    ],
                ],
            ],
        );
        self::assertSame(
            ['api.example' => ['192.0.2.3', '192.0.2.8', '2001:db8::1'], 'zeta.example' => ['192.0.2.9']],
            $config->data['resolver']['staticHosts'],
        );
    }

    public function testExactlyMaximumStaticAddressesArePreserved(): void
    {
        $addresses = [];
        for ($i = 1; $i <= 64; ++$i) {
            $addresses[] = '192.0.2.' . $i;
        }
        $expected = $addresses;
        sort($expected, SORT_STRING);
        $config = GuardConfig::fromArray(
            ['resolver' => ['maxAddresses' => 64, 'staticHosts' => ['addresses.example' => array_reverse($addresses)]]],
        );
        self::assertSame($expected, $config->data['resolver']['staticHosts']['addresses.example']);
        self::assertCount(64, $config->data['resolver']['staticHosts']['addresses.example']);
    }

    public function testConfiguredNetworkSetsAreMinimalAndSortedAcrossFamilies(): void
    {
        $config = GuardConfig::fromArray(
            [
                'deniedCidrs' => ['2001:db8:1::/48', '10.1.0.0/16', '10.0.0.0/8', '2001:db8::/32', '10.0.0.0/8'],
                'endpoints'   => ['erp' => $this->endpoint()],
            ],
        );
        self::assertSame(['10.0.0.0/8', '2001:db8::/32'], $config->data['deniedCidrs']);
        self::assertSame(['10.23.4.0/24', '2001:db8:1::/64'], $config->data['endpoints']['erp']['allowedCidrs']);
    }

    public function testEquivalentHostProfileAndNetworkOrderingKeepTheSameRevision(): void
    {
        $first = [
            'resolver' => [
                'staticHosts' => ['zeta.example' => ['192.0.2.3', '192.0.2.2', '192.0.2.2'], 'API.Example' => ['::ffff:192.0.2.8']],
            ],
            'deniedCidrs' => ['10.1.0.0/16', '10.0.0.0/8'],
            'endpoints'   => ['zeta' => $this->endpoint(), 'alpha' => $this->endpoint()],
        ];
        $second                            = $first;
        $second['resolver']['staticHosts'] = ['api.example' => ['192.0.2.8'], 'zeta.example' => ['192.0.2.2', '192.0.2.3']];
        $second['deniedCidrs']             = ['10.0.0.0/8'];
        $second['endpoints']               = array_reverse($second['endpoints'], true);
        $a                                 = GuardConfig::fromArray($first);
        $b                                 = GuardConfig::fromArray($second);
        self::assertSame($a->data, $b->data);
        self::assertSame($a->revision, $b->revision);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $a->revision);
        $second['redirects'] = ['max' => 2];
        self::assertNotSame($a->revision, GuardConfig::fromArray($second)->revision);
    }

    #[DataProvider('acceptedGlobalEdges')]
    public function testAcceptedGlobalBoundariesRemainAvailable(
        string $mode,
        array $resolver,
        int $redirects,
        bool $verify,
        int|float $sampleRate,
        string $hostMode,
        int $denyRate,
    ): void {
        $keyName = 'H' . str_repeat('X', 127);
        $config  = GuardConfig::fromArray(
            [
                'schemaVersion' => 1,
                'mode'          => $mode,
                'resolver'      => $resolver,
                'redirects'     => ['max' => $redirects],
                'tls'           => ['requireVerification' => $verify],
                'logging'       => [
                    'allowedSampleRate'      => $sampleRate,
                    'hostMode'               => $hostMode,
                    'hostHmacKeyEnv'         => $keyName,
                    'denyRateLimitPerMinute' => $denyRate,
                ],
            ],
        );
        self::assertSame($mode, $config->mode);
        foreach ($resolver as $key => $value) {
            self::assertSame($value, $config->data['resolver'][$key]);
        }
        self::assertSame(['max' => $redirects], $config->data['redirects']);
        self::assertSame(['requireVerification' => $verify], $config->data['tls']);
        self::assertSame(
            [
                'allowedSampleRate'      => $sampleRate,
                'hostMode'               => $hostMode,
                'hostHmacKeyEnv'         => $keyName,
                'denyRateLimitPerMinute' => $denyRate,
            ],
            $config->data['logging'],
        );
    }

    public function testCompleteEndpointKeepsAcceptedTextMethodAndTimestampEdges(): void
    {
        $id                      = 'A' . str_repeat('x', 63);
        $endpoint                = $this->endpoint();
        $endpoint['origin']      = 'HTTPS://API.Example:443';
        $endpoint['methods']     = ['POST', 'GET', 'POST', 'X' . str_repeat('Y', 63)];
        $endpoint['redirects']   = 'same-origin';
        $endpoint['purpose']     = str_repeat('Ä', 200);
        $endpoint['owner']       = str_repeat('ö', 120);
        $endpoint['reviewAfter'] = '2028-02-29';
        $endpoint['expiresAt']   = '2030-12-31T23:59:59.123456+02:30';
        $profile                 = GuardConfig::fromArray(['endpoints' => [$id => $endpoint]])->data['endpoints'][$id];
        self::assertSame('https://api.example', $profile['origin']);
        self::assertSame(['GET', 'POST', 'X' . str_repeat('Y', 63)], $profile['methods']);
        foreach (['redirects', 'purpose', 'owner', 'reviewAfter', 'expiresAt'] as $key) {
            self::assertSame($endpoint[$key], $profile[$key]);
        }
    }

    public function testExplicitLoopbackHostProfilesRemainAvailable(): void
    {
        $endpoint                  = $this->endpoint();
        $endpoint['origin']        = 'http://[::1]:8080';
        $endpoint['allowedCidrs']  = ['::1/128', '127.0.0.1/32'];
        $endpoint['allowLoopback'] = true;
        $profile                   = GuardConfig::fromArray(['endpoints' => ['local' => $endpoint]])->data['endpoints']['local'];
        self::assertSame('http://[::1]:8080', $profile['origin']);
        self::assertSame(['127.0.0.1/32', '::1/128'], $profile['allowedCidrs']);
        self::assertTrue($profile['allowLoopback']);
        self::assertSame('none', $profile['redirects']);
        self::assertNull($profile['reviewAfter']);
        self::assertNull($profile['expiresAt']);
    }

    public function testExactlyMaximumEndpointProfilesArePreservedAndSorted(): void
    {
        $endpoints = [];
        for ($i = 127; $i >= 0; --$i) {
            $endpoints[sprintf('endpoint-%03d', $i)] = $this->endpoint();
        }
        $profiles = GuardConfig::fromArray(['endpoints' => $endpoints])->data['endpoints'];
        self::assertCount(128, $profiles);
        self::assertSame('endpoint-000', array_key_first($profiles));
        self::assertSame('endpoint-127', array_key_last($profiles));
    }

    /** @return iterable<string, array{string,array<string,int>,int,bool,int|float,string,int}> */
    public static function acceptedGlobalEdges(): iterable
    {
        yield 'lower enforce' => [
            'enforce',
            ['cacheTtlSeconds' => 0, 'cacheMaxHosts' => 1, 'maxAddresses' => 1, 'maxCnameHops' => 0],
            0,
            false,
            0,
            'hash',
            1,
        ];
        yield 'upper observe' => [
            'observe',
            ['cacheTtlSeconds' => 5, 'cacheMaxHosts' => 1024, 'maxAddresses' => 64, 'maxCnameHops' => 8],
            10,
            true,
            1,
            'plain',
            10000,
        ];
        yield 'fractional disabled' => [
            'disabled',
            ['cacheTtlSeconds' => 3, 'cacheMaxHosts' => 32, 'maxAddresses' => 12, 'maxCnameHops' => 2],
            3,
            false,
            0.5,
            'hash',
            60,
        ];
    }

    /** @return array<string,mixed> */
    private function endpoint(): array
    {
        return [
            'origin'       => 'https://erp.example:8443',
            'allowedCidrs' => ['2001:db8:1::/64', '10.23.4.12/32', '10.23.4.0/24'],
            'methods'      => ['POST', 'GET', 'POST'],
            'purpose'      => 'Ordinary offline profile contract',
            'owner'        => 'Fixture maintainers',
        ];
    }

    #[DataProvider('outOfRangeScalars')]
    public function testOrdinaryOutOfRangeConfigurationHasTheFixedPolicyError(array $input): void
    {
        $this->expectException(\Netresearch\HttpGuard\PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        GuardConfig::fromArray($input);
    }

    /** @return iterable<string,array{array<string,mixed>}> */
    public static function outOfRangeScalars(): iterable
    {
        foreach (['cacheTtlSeconds' => [0, 5], 'cacheMaxHosts' => [1, 1024], 'maxAddresses' => [1, 64], 'maxCnameHops' => [0, 8]] as $key => [$minimum, $maximum]) {
            yield $key . ' below' => [['resolver' => [$key => $minimum - 1]]];
            yield $key . ' above' => [['resolver' => [$key => $maximum + 1]]];
            yield $key . ' fractional' => [['resolver' => [$key => 1.25]]];
        }
        yield 'redirects below' => [['redirects' => ['max' => -1]]];
        yield 'redirects above' => [['redirects' => ['max' => 11]]];
        yield 'deny rate below' => [['logging' => ['denyRateLimitPerMinute' => 0]]];
        yield 'deny rate above' => [['logging' => ['denyRateLimitPerMinute' => 10001]]];
        yield 'sample below' => [['logging' => ['allowedSampleRate' => -0.25]]];
        yield 'sample above' => [['logging' => ['allowedSampleRate' => 1.25]]];
        yield 'tls wrong type' => [['tls' => ['requireVerification' => 1]]];
        yield 'schema wrong version' => [['schemaVersion' => 2]];
    }

    #[DataProvider('ordinaryStaticNames')]
    public function testOrdinaryDnsLabelsWithDigitsRemainAvailable(string $host): void
    {
        $config = GuardConfig::fromArray(['resolver' => ['staticHosts' => [$host => ['192.0.2.1']]]]);
        self::assertSame([$host => ['192.0.2.1']], $config->data['resolver']['staticHosts']);
    }

    /** @return iterable<string,array{string}> */
    public static function ordinaryStaticNames(): iterable
    {
        yield 'starts with digit' => ['1api.example'];
        yield 'ends with digit' => ['api.example1'];
        yield 'ordinary name' => ['api.example'];
    }
}
