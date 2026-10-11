<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use Closure;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\Client\ClientStackConfiguration;
use Netresearch\HttpGuard\Client\ClientStackProviderInterface;
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\TerminalGuardMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ClientFactoryContractTest extends TestCase
{
    #[DataProvider('forbiddenDefaults')]
    public function testTransportControlDefaultsCannotBypassEnforcement(string $key, mixed $value): void
    {
        $factory = $this->factory(GuardConfig::fromArray([]));
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('option_forbidden');
        $factory->createTransport([$key => $value]);
    }

    public static function forbiddenDefaults(): iterable
    {
        foreach ([
            'handler',
            'transport_sharing',
            'curl',
            'curl_multi',
            'stream_context',
            'nr_http_guard_context',
            'nr_http_guard_grant',
            'nr_http_guard_envelope',
            'nr_http_guard_invocation',
            '_curl_retries',
        ] as $key) {
            yield $key . ' present-null' => [$key, null];
            yield $key . ' present-value' => [$key, []];
        }
    }

    public function testEquivalentConfigurationObjectsCannotCrossRegistryOwnership(): void
    {
        $clock    = new SystemClock();
        $first    = GuardConfig::fromArray([]);
        $registry = new PolicyRegistry($first, $clock);
        $second   = GuardConfig::fromArray([]);
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        new GuardedClientFactory($this->engine($registry, $clock), $second, $registry);
    }

    public function testDefaultRedirectLimitAndExplicitFalseRemainDistinct(): void
    {
        $factory = $this->factory(GuardConfig::fromArray(['redirects' => ['max' => 2]]));
        $default = $factory->createTransport();
        self::assertSame(
            ['max' => 2, 'strict' => false, 'protocols' => ['http', 'https']],
            $default->client->getConfig('allow_redirects'),
        );
        self::assertFalse($default->client->getConfig('idn_conversion'));
        self::assertFalse($factory->createTransport(['allow_redirects' => false])->client->getConfig('allow_redirects'));
        self::assertSame(0, $default->driver->counters()['nativeConstructed']);
    }

    public function testEndpointCanDisableRedirectsWithoutAffectingPublicScope(): void
    {
        $config = GuardConfig::fromArray(
            [
                'endpoints' => [
                    'erp' => [
                        'origin'       => 'https://erp.example',
                        'allowedCidrs' => ['203.0.115.0/24'],
                        'methods'      => ['GET'],
                        'purpose'      => 'synthetic fixture',
                        'owner'        => 'test owner',
                        'redirects'    => 'none',
                    ],
                ],
            ],
        );
        $factory = $this->factory($config);
        self::assertFalse($factory->createTransport([], 'erp')->client->getConfig('allow_redirects'));
        self::assertIsArray($factory->createTransport()->client->getConfig('allow_redirects'));
    }

    public function testPublicFetchStripsInheritedBodiesCredentialsAndRoutingDefaults(): void
    {
        $history  = [];
        $provider = $this->provider(
            static function (
                BoundaryMiddleware $boundary,
                TerminalGuardMiddleware $terminal,
            ) use (&$history): ClientStackConfiguration {
                $stack = HandlerStack::create(new MockHandler([new Response(200, [], 'synthetic')]));
                $stack->push($boundary, 'nr/http-guard-boundary');
                $stack->push(Middleware::history($history), 'history');
                $stack->push($terminal, 'nr/http-guard-terminal');

                return new ClientStackConfiguration(
                    $stack,
                    [
                        'auth'          => ['synthetic', 'synthetic'],
                        'cookies'       => false,
                        'cert'          => 'synthetic.pem',
                        'ssl_key'       => 'synthetic.key',
                        'cert_type'     => 'PEM',
                        'ssl_key_type'  => 'PEM',
                        'headers'       => ['Authorization' => 'synthetic', 'X-Inherited' => 'synthetic'],
                        'body'          => 'synthetic-body',
                        'json'          => ['synthetic' => true],
                        'form_params'   => ['synthetic' => true],
                        'multipart'     => [],
                        'query'         => ['inherited' => 'synthetic'],
                        'base_uri'      => 'https://inherited.example',
                        'sink'          => 'synthetic.txt',
                        'allowed_hosts' => ['inherited.example'],
                    ],
                );
            },
        );
        $factory = $this->factory(GuardConfig::fromArray(['mode' => 'disabled']), $provider);
        self::assertSame(
            'synthetic',
            (string) $factory->publicFetch()->fetch(new Uri('https://public.example/path?caller=retained'))->getBody(),
        );
        self::assertCount(1, $history);
        self::assertSame('https://public.example/path?caller=retained', (string) $history[0]['request']->getUri());
        self::assertSame('', (string) $history[0]['request']->getBody());
        self::assertFalse($history[0]['request']->hasHeader('Authorization'));
        self::assertFalse($history[0]['request']->hasHeader('X-Inherited'));
        foreach ([
            'auth',
            'cert',
            'ssl_key',
            'cert_type',
            'ssl_key_type',
            'body',
            'json',
            'form_params',
            'multipart',
            'query',
            'base_uri',
            'sink',
            'allowed_hosts',
        ] as $name) {
            self::assertArrayNotHasKey($name, $history[0]['options']);
        }
        self::assertFalse($history[0]['options']['cookies']);
    }

    #[DataProvider('invalidStacks')]
    public function testMalformedOrReorderedProviderStacksAreRejected(string $shape): void
    {
        $provider = $this->provider(
            static function (
                BoundaryMiddleware $boundary,
                TerminalGuardMiddleware $terminal,
            ) use ($shape): ClientStackConfiguration {
                $stack = HandlerStack::create();
                $stack->push($boundary, 'nr/http-guard-boundary');
                $stack->push($terminal, 'nr/http-guard-terminal');
                $property = new ReflectionProperty(HandlerStack::class, 'stack');
                $entries  = $property->getValue($stack);
                switch ($shape) {
                    case 'non-list-inventory':
                        $entries = ['entry' => $entries[0]];
                        break;
                    case 'non-array-entry':
                        $entries[0] = null;
                        break;
                    case 'non-list-entry':
                        $entries[0] = ['callable' => $entries[0][0], 'name' => $entries[0][1]];
                        break;
                    case 'short-entry':
                        $entries[0] = [$entries[0][0]];
                        break;
                    case 'long-entry':
                        $entries[0][] = 'extra';
                        break;
                    case 'non-callable':
                        $entries[0][0] = null;
                        break;
                    case 'invalid-name':
                        $entries[0][1] = 42;
                        break;
                    case 'missing-boundary':
                        array_splice($entries, -2, 1);
                        break;
                    case 'missing-terminal':
                        array_pop($entries);
                        break;
                    case 'duplicate-boundary':
                        array_splice($entries, -1, 0, [[$boundary, 'duplicate']]);
                        break;
                    case 'duplicate-terminal':
                        $entries[] = [$terminal, 'duplicate'];
                        break;
                    case 'foreign-boundary':
                        $entries[4][0] = clone $boundary;
                        break;
                    case 'foreign-terminal':
                        $entries[5][0] = clone $terminal;
                        break;
                    case 'reversed-guards':
                        [$entries[4], $entries[5]] = [$entries[5], $entries[4]];
                        break;
                    case 'after-terminal':
                        $entries[] = [static fn (callable $handler): callable => $handler, 'late'];
                        break;
                    case 'foreign-prefix-name':
                        $entries[0][1] = 'foreign';
                        break;
                    case 'foreign-prefix-closure':
                        $entries[0][0] = static fn (callable $handler): callable => $handler;
                        break;
                    case 'unexpected-prefix-entry':
                        array_splice($entries, 4, 0, [[static fn (callable $handler): callable => $handler, 'foreign']]);
                        break;
                    case 'forged-core-wrapper':
                        array_splice(
                            $entries,
                            4,
                            0,
                            [[static fn (callable $handler): callable => $handler, 'typo3_allowed_hosts']],
                        );
                        break;
                    case 'too-many-prefix-entries':
                        array_splice(
                            $entries,
                            4,
                            0,
                            [
                                [static fn (callable $handler): callable => $handler, 'extra-one'],
                                [static fn (callable $handler): callable => $handler, 'extra-two'],
                            ],
                        );
                        break;
                }
                $property->setValue($stack, $entries);

                return new ClientStackConfiguration($stack);
            },
        );
        $factory = $this->factory(GuardConfig::fromArray([]), $provider);
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        $factory->createTransport();
    }

    public static function invalidStacks(): iterable
    {
        foreach ([
            'non-list-inventory',
            'non-array-entry',
            'non-list-entry',
            'short-entry',
            'long-entry',
            'non-callable',
            'invalid-name',
            'missing-boundary',
            'missing-terminal',
            'duplicate-boundary',
            'duplicate-terminal',
            'foreign-boundary',
            'foreign-terminal',
            'reversed-guards',
            'after-terminal',
            'foreign-prefix-name',
            'foreign-prefix-closure',
            'unexpected-prefix-entry',
            'forged-core-wrapper',
            'too-many-prefix-entries',
        ] as $shape) {
            yield $shape => [$shape];
        }
    }

    public function testRegistryDriftIsRejectedBeforePolicyResolutionOrTransport(): void
    {
        $stack      = null;
        $assertions = 0;
        $provider   = $this->provider(
            static function (
                BoundaryMiddleware $boundary,
                TerminalGuardMiddleware $terminal,
            ) use (&$stack, &$assertions): ClientStackConfiguration {
                $stack = HandlerStack::create(new MockHandler([new Response(200)]));
                $stack->push($boundary, 'nr/http-guard-boundary');
                $stack->push($terminal, 'nr/http-guard-terminal');

                return new ClientStackConfiguration(
                    $stack,
                    [],
                    static function () use (&$assertions): void {
                        ++$assertions;
                    },
                );
            },
        );
        $binding = $this->factory(GuardConfig::fromArray([]), $provider)->createTransport();
        self::assertInstanceOf(HandlerStack::class, $stack);
        $stack->push(static fn (callable $handler): callable => $handler, 'late');
        try {
            $binding->client->send(new Request('GET', 'https://synthetic.example'));
            self::fail('A late provider mutation was accepted');
        } catch (PolicyException $error) {
            self::assertSame('configuration_invalid', $error->reasonCode());
        }
        self::assertSame(1, $assertions);
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
        self::assertSame(0, $binding->driver->counters()['created']);
    }

    private function provider(Closure $create): ClientStackProviderInterface
    {
        return new class ($create) implements ClientStackProviderInterface {
            public function __construct(private readonly Closure $create) {}

            public function create(
                BoundaryMiddleware $boundary,
                TerminalGuardMiddleware $terminal,
            ): ClientStackConfiguration {
                return ($this->create)($boundary, $terminal);
            }
        };
    }

    private function factory(GuardConfig $config, ?ClientStackProviderInterface $provider = null): GuardedClientFactory
    {
        $clock    = new SystemClock();
        $registry = new PolicyRegistry($config, $clock);

        return new GuardedClientFactory($this->engine($registry, $clock), $config, $registry, $provider);
    }

    private function engine(PolicyRegistry $registry, SystemClock $clock): PolicyEngine
    {
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->expects(self::never())->method('resolve');

        return new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            new NullDecisionReporter(),
        );
    }
}
