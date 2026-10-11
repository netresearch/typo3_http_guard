<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Psr7\Request;
use LogicException;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\Resolution;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\HttpGuard\Transport\TransferDriver;
use Netresearch\HttpGuard\Transport\TransferLease;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class TransferLeaseInputTest extends TestCase
{
    #[DataProvider('malformedNativeOptions')]
    public function testMalformedNativeOptionsFailBeforeLeaseOrNativeRegistration(array $options): void
    {
        $config   = GuardConfig::fromArray([]);
        $clock    = new SystemClock();
        $registry = new PolicyRegistry($config, $clock);
        $resolver = new class implements ResolverInterface {
            public function resolve(string $host): Resolution
            {
                throw new LogicException('constructor must not resolve');
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
        $driver  = new TransferDriver();
        $lease   = null;
        $failure = null;
        try {
            $lease = new TransferLease(
                $driver,
                $engine,
                $registry->newContext(),
                new Request('GET', 'https://api.example'),
                $options,
            );
        } catch (PolicyException $error) {
            $failure = $error;
        } finally {
            $lease?->promise()->cancel();
        }
        self::assertInstanceOf(PolicyException::class, $failure);
        self::assertSame('option_forbidden', $failure->reasonCode());
        self::assertSame(
            ['created' => 0, 'released' => 0, 'active' => 0, 'peak' => 0, 'nativeConstructed' => 0],
            $driver->counters(),
        );
    }

    /** @return iterable<string,array{array<string,mixed>}> */
    public static function malformedNativeOptions(): iterable
    {
        yield 'raw controls are not an array' => [['curl' => 'malformed']];
        yield 'headers callback is not callable' => [['on_headers' => 'missing_synthetic_callback']];
        yield 'statistics callback is not callable' => [['on_stats' => new stdClass()]];
    }
}
