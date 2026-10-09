<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use LogicException;
use Netresearch\HttpGuard\{AddressClassifier, DecisionEvent, DecisionReporterInterface, PolicyEngine, PolicyException, PolicyRegistry, Resolution, ResolverInterface, SystemClock, TargetNormalizer};
use Netresearch\NrHttpGuard\Configuration\ConfigurationLoader;
use Netresearch\NrHttpGuard\Http\{RawRequestFactoryRegistration, RequestFactoryCompatibility};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;

final class RawRequestFactoryModeTest extends TestCase
{
    public function testRawFailureIsEnforcedDiagnosedOrTransparentAccordingToMode(): void
    {
        $original = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        try {
            foreach (['enforce', 'observe', 'disabled'] as $mode) {
                $history = [];
                $mock    = new MockHandler([new Response(200)]);
                $stack   = HandlerStack::create($mock);
                $stack->push(Middleware::history($history));
                $replacement                = RequestFactoryCompatibility::replacementClass();
                $GLOBALS['TYPO3_CONF_VARS'] = [
                    'SYS'     => ['Objects' => [RequestFactory::class => ['className' => $replacement]]],
                    'HTTP'    => ['verify' => true, 'allowed_hosts' => [], 'handler' => $stack],
                    'EXTCONF' => ['nr_http_guard' => ['mode' => $mode]],
                ];
                $loader   = new ConfigurationLoader();
                $config   = $loader->load();
                $clock    = new SystemClock();
                $registry = new PolicyRegistry($config, $clock);
                $reporter = new class implements DecisionReporterInterface {
                    public array $events = [];

                    public function report(DecisionEvent $event): void
                    {
                        $this->events[] = $event;
                    }
                };
                $resolver = new class implements ResolverInterface {
                    public function resolve(string $canonicalHost): Resolution
                    {
                        throw new LogicException('Raw syntax must be diagnosed without DNS');
                    }
                };
                $engine = new PolicyEngine(
                    new TargetNormalizer(),
                    new AddressClassifier(),
                    $resolver,
                    $registry,
                    $clock,
                    $reporter,
                );
                $guarded = null;
                $lookup  = static function () use (&$guarded) {
                    return $guarded;
                };
                $registration = new RawRequestFactoryRegistration($lookup, $lookup);
                $guzzle       = new GuzzleClientFactory();
                $core         = new RequestFactory($guzzle);
                $guarded      = new $replacement($guzzle, $loader, $registration, $engine, $registry, $core);
                $raw          = 'http://guard.test:8080/synthetic-raw-secret#';
                if ($mode === 'enforce') {
                    try {
                        $guarded->request($raw);
                        self::fail('Forbidden raw target accepted');
                    } catch (PolicyException $error) {
                        self::assertSame('invalid_target', $error->reasonCode());
                    }
                    self::assertCount(0, $history);
                } else {
                    self::assertSame(
                        200,
                        $guarded->request($raw, options: ['legacy_opaque_option' => true])->getStatusCode(),
                    );
                    self::assertCount(1, $history);
                    self::assertSame(
                        'http://guard.test:8080/synthetic-raw-secret',
                        (string) $history[0]['request']->getUri(),
                    );
                    self::assertTrue($history[0]['options']['legacy_opaque_option']);
                }
                if ($mode === 'disabled') {
                    self::assertCount(0, $reporter->events);
                } else {
                    self::assertCount(1, $reporter->events);
                    self::assertSame($mode === 'observe' ? 'would_deny' : 'deny', $reporter->events[0]->decision);
                    self::assertSame('invalid_target', $reporter->events[0]->reasonCode);
                    self::assertStringNotContainsString(
                        'synthetic-raw-secret',
                        json_encode($reporter->events[0], JSON_THROW_ON_ERROR),
                    );
                }
            }
        } finally {
            if ($original === null) {
                unset($GLOBALS['TYPO3_CONF_VARS']);
            } else {
                $GLOBALS['TYPO3_CONF_VARS'] = $original;
            }
        }
    }
}
