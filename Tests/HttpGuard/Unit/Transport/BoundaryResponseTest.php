<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use LogicException;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\DecisionReporter;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\Resolution;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\InvocationRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class BoundaryResponseTest extends TestCase
{
    #[DataProvider('malformedResponses')]
    public function testMalformedFulfillmentHasFixedPolicyFailureAndTelemetry(mixed $response): void
    {
        $config   = GuardConfig::fromArray([]);
        $clock    = new SystemClock();
        $registry = new PolicyRegistry($config, $clock);
        $context  = $registry->newContext();
        $rows     = [];
        $reporter = new DecisionReporter(
            $config,
            $clock,
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
        );
        $resolver = new class implements ResolverInterface {
            public function resolve(string $host): Resolution
            {
                throw new LogicException('response seam must not resolve');
            }
        };
        $engine   = new PolicyEngine(new TargetNormalizer(), new AddressClassifier(), $resolver, $registry, $clock, $reporter);
        $boundary = new BoundaryMiddleware($engine, $config, $registry, $context, new InvocationRegistry());
        $handler  = $boundary(static fn () => Create::promiseFor($response));
        try {
            $handler(new Request('GET', 'https://api.example'), ['allow_redirects' => false])->wait();
            self::fail('Malformed fulfillment was accepted');
        } catch (PolicyException $error) {
            self::assertSame('transport_unsupported', $error->reasonCode());
            self::assertSame('Outbound HTTP policy: transport_unsupported', $error->getMessage());
        }
        self::assertCount(1, $rows);
        self::assertSame('deny', $rows[0]['decision']);
        self::assertSame('transport_unsupported', $rows[0]['reasonCode']);
    }

    /** @return iterable<string,array{mixed}> */
    public static function malformedResponses(): iterable
    {
        yield 'scalar' => ['synthetic sensitive fulfillment'];
        yield 'integer' => [200];
        yield 'null' => [null];
        yield 'non-response object' => [new stdClass()];
    }
}
