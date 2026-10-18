<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use GuzzleHttp\Psr7\Request;
use LogicException;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\DecisionReporter;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\Resolution;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use PHPUnit\Framework\TestCase;

final class BoundPolicyTelemetryContractTest extends TestCase
{
    public function testLoopbackOptInRetainsOtherNarrowlyBoundAddresses(): void
    {
        $rows                = [];
        [$engine, $registry] = $this->stack(
            [
                'endpoints' => [
                    'erp' => [
                        'origin'        => 'https://10.23.4.12',
                        'allowedCidrs'  => ['10.23.4.12/32'],
                        'allowLoopback' => true,
                        'methods'       => ['GET'],
                        'purpose'       => 'Offline endpoint boundary',
                        'owner'         => 'Test maintainers',
                    ],
                ],
            ],
            $rows,
        );
        $plan = $engine->plan(new Request('GET', 'https://10.23.4.12/'), $registry->newContext('erp'));
        self::assertSame(['10.23.4.12'], $plan->addresses);
        self::assertSame('erp', $plan->profileId);
        self::assertSame('allow', $rows[0]['decision']);
    }

    public function testGeneratedCorrelationRemainsPresentAfterRealReporterValidation(): void
    {
        $rows                = [];
        [$engine, $registry] = $this->stack([], $rows);
        $context             = $registry->newContext();
        $request             = new Request('GET', 'https://8.8.8.8/');
        $engine->plan($request, $context);
        self::assertSame('allow', $engine->evaluate($request, $context)->decision);
        self::assertCount(2, $rows);
        foreach ($rows as $row) {
            self::assertIsString($row['correlationId']);
            self::assertMatchesRegularExpression('/^[0-9a-f]{24,64}$/D', $row['correlationId']);
        }
    }

    /** @param array<string,mixed> $configuration
     * @param list<array<string,mixed>> $rows
     *
     * @return array{PolicyEngine,PolicyRegistry} */
    private function stack(array $configuration, array &$rows): array
    {
        $configuration['logging'] = ['allowedSampleRate' => 1];
        $config                   = GuardConfig::fromArray($configuration);
        $clock                    = new SystemClock();
        $registry                 = new PolicyRegistry($config, $clock);
        $resolver                 = new class implements ResolverInterface {
            public function resolve(string $canonicalHost): Resolution
            {
                throw new LogicException('Literal policy controls must never resolve DNS');
            }
        };
        $reporter = new DecisionReporter(
            $config,
            $clock,
            static function (array $row) use (&$rows): void {
                $rows[] = $row;
            },
        );

        return [
            new PolicyEngine(new TargetNormalizer(), new AddressClassifier(), $resolver, $registry, $clock, $reporter),
            $registry,
        ];
    }
}
