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

final class GuardConfigurationBoundaryContractTest extends TestCase
{
    public function testEmptyConfigurationKeepsDocumentedRestrictiveDefaults(): void
    {
        $config = GuardConfig::fromArray([]);
        self::assertSame('enforce', $config->mode);
        self::assertSame(
            [
                'schemaVersion' => 1,
                'mode'          => 'enforce',
                'deniedCidrs'   => [],
                'endpoints'     => [],
                'resolver'      => [
                    'staticHosts'     => [],
                    'cacheTtlSeconds' => 5,
                    'cacheMaxHosts'   => 32,
                    'maxAddresses'    => 64,
                    'maxCnameHops'    => 8,
                ],
                'redirects' => ['max' => 5],
                'tls'       => ['requireVerification' => false],
                'logging'   => [
                    'allowedSampleRate'      => 0,
                    'hostMode'               => 'hash',
                    'hostHmacKeyEnv'         => null,
                    'denyRateLimitPerMinute' => 60,
                ],
            ],
            $config->data,
        );
    }

    #[DataProvider('invalidGlobalShapes')]
    public function testConfigurationNeverCoercesMalformedShapes(array $data): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        GuardConfig::fromArray($data);
    }

    public static function invalidGlobalShapes(): iterable
    {
        yield 'numeric root key' => [[0 => true]];
        foreach (['resolver', 'redirects', 'tls', 'logging'] as $section) {
            yield $section . ' scalar' => [[$section => false]];
            yield $section . ' unknown key' => [[$section => ['unexpected' => true]]];
            yield $section . ' numeric key' => [[$section => [0 => true]]];
        }
        foreach ([false, '0', '0.5', null, [], NAN, -INF, INF] as $n => $rate) {
            yield 'sample shape ' . $n => [['logging' => ['allowedSampleRate' => $rate]]];
        }
        foreach (['9KEY', ' KEY', 'KEY ', "KEY\n", str_repeat('K', 129), 1, false, ''] as $n => $key) {
            yield 'HMAC name ' . $n => [['logging' => ['hostHmacKeyEnv' => $key]]];
        }
        foreach ([false, ['bad' => '8.8.8.8'], [], ['8.8.8.8', 9]] as $n => $ips) {
            yield 'static addresses shape ' . $n => [['resolver' => ['staticHosts' => ['shape.example' => $ips]]]];
        }
        yield 'static nonarray' => [['resolver' => ['staticHosts' => 'shape.example']]];
        yield 'static numeric host' => [['resolver' => ['staticHosts' => ['127.0.0.1' => ['8.8.8.8']]]]];
        yield 'static IPv6 host' => [['resolver' => ['staticHosts' => ['::1' => ['8.8.8.8']]]]];
        yield 'static numeric array key' => [['resolver' => ['staticHosts' => [1 => ['8.8.8.8']]]]];
        yield 'static cap before deduplication' => [['resolver' => ['maxAddresses' => 1, 'staticHosts' => ['shape.example' => ['8.8.8.8', '8.8.8.8']]]]];
        yield 'endpoints scalar' => [['endpoints' => false]];
        yield 'denied set associative' => [['deniedCidrs' => ['one' => '10.0.0.0/8']]];
        yield 'denied set scalar' => [['deniedCidrs' => false]];
        yield 'denied element not string' => [['deniedCidrs' => [false]]];
    }

    #[DataProvider('invalidEndpointShapes')]
    public function testEndpointGrantRequiresExactTypedAuditableFields(
        array $overrides,
        ?string $missing = null,
        string $id = 'api',
    ): void {
        $endpoint = array_replace($this->endpoint(), $overrides);
        if ($missing !== null) {
            unset($endpoint[$missing]);
        }
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        GuardConfig::fromArray(['endpoints' => [$id => $endpoint]]);
    }

    public static function invalidEndpointShapes(): iterable
    {
        foreach (['origin', 'allowedCidrs', 'methods', 'purpose', 'owner'] as $field) {
            yield 'missing ' . $field => [[], $field];
        }
        foreach (['!api', 'api!', "api\n", str_repeat('a', 65)] as $id) {
            yield 'identifier ' . bin2hex($id) => [[], null, $id];
        }
        foreach ([
            'origin'        => false,
            'allowedCidrs'  => false,
            'methods'       => false,
            'purpose'       => false,
            'owner'         => false,
            'allowLoopback' => 1,
            'redirects'     => 'all',
            'reviewAfter'   => false,
            'expiresAt'     => false,
        ] as $field => $value) {
            yield 'typed ' . $field => [[$field => $value]];
        }
        foreach ([[], ['GET' => 'GET'], [true], ['GET' . "\n"], ['cOnNeCt'], [str_repeat('G', 65)]] as $n => $methods) {
            yield 'methods ' . $n => [['methods' => $methods]];
        }
        foreach ([' ', "bad\x00text", 'badtext', "\xff", str_repeat('x', 201)] as $n => $value) {
            yield 'purpose ' . $n => [['purpose' => $value]];
        }
        yield 'owner 121' => [['owner' => str_repeat('x', 121)]];
        foreach (['2025-02-29', '2028-02-30', '2028-13-01', "2028-02-29\n"] as $date) {
            yield 'date ' . bin2hex($date) => [['reviewAfter' => $date]];
        }
        foreach ([
            '2030-01-01T23:60:00Z',
            '2030-01-01T23:59:60Z',
            '2030-01-01T24:00:00Z',
            '2030-01-01T12:00:00+24:00',
            '2030-01-01T12:00:00+01:60',
            '2030-01-01T12:00:00.1234567Z',
            '2030-01-01T12:00:00.1Z' . "\n",
            '2030-01-01 12:00:00Z',
        ] as $date) {
            yield 'instant ' . bin2hex($date) => [['expiresAt' => $date]];
        }
        foreach ([
            'https://api.example/',
            'https://api.example?x=1',
            'https://user@api.example',
            "https://api.example\n",
            'https://api.example:65536',
        ] as $origin) {
            yield 'origin ' . bin2hex($origin) => [['origin' => $origin]];
        }
        foreach ([[], ['10.0.0.0/23'], ['fd00::/63'], ['127.0.0.1/32'], ['::1/128'], ['::ffff:10.0.0.0/120']] as $n => $cidrs) {
            yield 'narrow scope ' . $n => [['allowedCidrs' => $cidrs]];
        }
    }

    public function testUnboundLoopbackDenyAndMethodOrderHaveUnambiguousMeaning(): void
    {
        $endpoint            = $this->endpoint();
        $endpoint['methods'] = ['POST', 'POST', 'GET'];
        $config              = GuardConfig::fromArray(['deniedCidrs' => ['127.0.0.0/8', '::1/128'], 'endpoints' => ['api' => $endpoint]]);
        self::assertSame(['127.0.0.0/8', '::1/128'], $config->data['deniedCidrs']);
        self::assertSame(['GET', 'POST'], $config->data['endpoints']['api']['methods']);
        self::assertFalse($config->data['endpoints']['api']['allowLoopback']);
        self::assertSame('none', $config->data['endpoints']['api']['redirects']);
    }

    private function endpoint(): array
    {
        return [
            'origin'       => 'https://api.example',
            'allowedCidrs' => ['10.23.4.0/24'],
            'methods'      => ['GET'],
            'purpose'      => 'Business contract',
            'owner'        => 'Application maintainers',
        ];
    }

    public function testCanonicalDisjointNetworkOrderIsStableAcrossFamiliesAndPrefixes(): void
    {
        $networks = ['2001:db8::/32', '192.168.0.0/16', '172.16.0.0/12', '10.0.0.0/8', 'fd00::/8'];
        $first    = GuardConfig::fromArray(['deniedCidrs' => $networks]);
        self::assertSame(
            ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fd00::/8', '2001:db8::/32'],
            $first->data['deniedCidrs'],
        );
        self::assertSame(
            $first->revision,
            GuardConfig::fromArray(['deniedCidrs' => array_reverse($networks)])->revision,
        );
    }

    #[DataProvider('acceptedReviewInstants')]
    public function testValidLeapDateAndMaximumTimezoneRemainUsable(string $instant): void
    {
        $endpoint                = $this->endpoint();
        $endpoint['expiresAt']   = $instant;
        $endpoint['reviewAfter'] = '2028-02-29';
        $config                  = GuardConfig::fromArray(['endpoints' => ['api' => $endpoint]]);
        self::assertSame($instant, $config->data['endpoints']['api']['expiresAt']);
        self::assertSame('2028-02-29', $config->data['endpoints']['api']['reviewAfter']);
    }

    public static function acceptedReviewInstants(): iterable
    {
        foreach (['2028-02-29T23:59:59+23:59', '2028-02-29T23:59:59-23:59', '2028-02-29T00:00:00.1Z'] as $instant) {
            yield [$instant];
        }
    }
}
