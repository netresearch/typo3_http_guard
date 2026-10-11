<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use LogicException;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\Resolution;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class NativeClientDefaultsTest extends TestCase
{
    /** Native construction stores these defaults; it does not send a request. */
    public function testNativeSdkAcceptsDefaultsOutsideRequestPhpDoc(): void
    {
        $options = [
            'handler'       => HandlerStack::create(),
            'expect'        => 0.5,
            'on_headers'    => null,
            'on_stats'      => null,
            'on_trailers'   => null,
            'native_custom' => new stdClass(),
        ];
        $client = new Client($options);
        $stored = $client->getConfig();
        foreach ($options as $name => $value) {
            self::assertArrayHasKey($name, $stored);
            self::assertSame($value, $stored[$name]);
        }
    }

    #[DataProvider('transparentModes')]
    public function testObserveAndDisabledRetainNativeDefaultsWithoutConstructingTransfer(string $mode): void
    {
        $config   = GuardConfig::fromArray(['mode' => $mode]);
        $clock    = new SystemClock();
        $registry = new PolicyRegistry($config, $clock);
        $resolver = new class implements ResolverInterface {
            public function resolve(string $host): Resolution
            {
                throw new LogicException('configuration must not resolve');
            }
        };
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            new NullDecisionReporter(),
        );
        $factory = new GuardedClientFactory($engine, $config, $registry);
        $headers = static function (): void {};
        $stats   = static function (): void {};
        $custom  = new stdClass();
        $options = [
            'proxy'         => 'http://synthetic.proxy.example:3128',
            'stream'        => true,
            'read_timeout'  => 0.25,
            'expect'        => 0.5,
            'on_headers'    => $headers,
            'on_stats'      => $stats,
            'on_trailers'   => null,
            'progress'      => null,
            'auth'          => ['synthetic-user', 'synthetic-password', 'basic'],
            'cert'          => ['synthetic-client.pem', 'synthetic-password'],
            'ssl_key'       => ['synthetic-key.pem', 'synthetic-password'],
            'query'         => ['custom' => 'retained'],
            'verify'        => false,
            'cookies'       => false,
            'native_custom' => $custom,
        ];
        $binding = $factory->createTransport($options);
        $stored  = $binding->client->getConfig();
        foreach ($options as $name => $value) {
            self::assertArrayHasKey($name, $stored);
            self::assertSame($value, $stored[$name]);
        }
        self::assertSame(
            ['created' => 0, 'released' => 0, 'active' => 0, 'peak' => 0, 'nativeConstructed' => 0],
            $binding->driver->counters(),
        );
    }

    /** @return iterable<string,array{string}> */
    public static function transparentModes(): iterable
    {
        yield 'observe' => ['observe'];
        yield 'disabled' => ['disabled'];
    }
}
