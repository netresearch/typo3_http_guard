<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Client;

use Closure;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Netresearch\HttpGuard\{AddressClassifier, GuardConfig, NullDecisionReporter, PolicyEngine, PolicyException, PolicyRegistry, ResolverInterface, SystemClock, TargetNormalizer};
use Netresearch\HttpGuard\Client\{ClientStackConfiguration, ClientStackProviderInterface, GuardedClientFactory};
use Netresearch\HttpGuard\Transport\{BoundaryMiddleware, TerminalGuardMiddleware};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use TYPO3\CMS\Core\Http\Client\AllowedHostsMiddleware;

final class FactoryMutationBoundaryTest extends TestCase
{
    public function testDirectMiddlewarePairDefaultsToOrdinaryRequests(): void
    {
        $pair     = $this->factory()->middlewarePair();
        $response = new Response(201);
        $handle   = ($pair->boundary)(
            static function ($request, array $options) use ($response) {
                self::assertSame('POST', $request->getMethod());
                self::assertSame('payload', (string) $request->getBody());
                self::assertFalse($options['nr_http_guard_envelope']->publicFetch);

                return Create::promiseFor($response);
            }
        );
        self::assertSame($response, $handle(new Request('POST', 'https://public.example', [], 'payload'), [])->wait());
    }

    public function testExplicitProviderRedirectDefaultIsNotOverwritten(): void
    {
        self::assertFalse(
            $this->factory(defaults: ['allow_redirects' => false])->createTransport()->client->getConfig(
                'allow_redirects',
            ),
        );
    }

    #[DataProvider('guards')]
    public function testBothPublishedGuardsRecheckProviderRegistry(string $side): void
    {
        $guards  = [];
        $factory = $this->factory(
            static function ($stack, $boundary, $terminal) use (&$guards): void {
                $guards = ['boundary' => $boundary, 'terminal' => $terminal];
            },
            assertion: static function (): never {
                throw new PolicyException('configuration_invalid');
            },
        );
        $factory->createTransport();
        $calls  = 0;
        $handle = $guards[$side](
            static function () use (&$calls) {
                ++$calls;

                return Create::promiseFor(new Response(200));
            },
        );
        try {
            $handle(new Request('GET', 'https://public.example'), [])->wait();
            self::fail('Registry assertion was skipped');
        } catch (PolicyException $error) {
            self::assertSame('configuration_invalid', $error->reasonCode());
        }
        self::assertSame(0, $calls);
    }

    public static function guards(): iterable
    {
        yield 'outer boundary' => ['boundary'];
        yield 'terminal boundary' => ['terminal'];
    }

    #[DataProvider('corePrefixes')]
    public function testOnlyTheExactCoreAllowedHostsPrefixIsAccepted(string $name, bool $supported): void
    {
        if (!class_exists(AllowedHostsMiddleware::class)) {
            self::markTestSkipped('This installed Core graph has no AllowedHostsMiddleware prefix.');
        }
        // Each data row supplies a name without changing the real Core middleware class.
        $factory = $this->factory(
            static function ($stack) use ($name): void {
                $stack->before(
                    'guard-boundary',
                    new AllowedHostsMiddleware('mutation-contract', ['public.example']),
                    $name,
                );
            },
        );
        if (!$supported) {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('configuration_invalid');
        }
        $binding = $factory->createTransport();
        if ($supported) {
            self::assertInstanceOf(HandlerStack::class, $binding->client->getConfig('handler'));
            self::assertSame(0, $binding->driver->counters()['created']);
        }
    }

    public static function corePrefixes(): iterable
    {
        yield 'supported Core prefix' => ['typo3_allowed_hosts', true];
        yield 'foreign name on same Core class' => ['foreign_allowed_hosts', false];
    }

    #[DataProvider('inventories')]
    public function testMalformedStackInventoriesFailClosed(string $shape, bool $supported): void
    {
        $factory = $this->factory(
            static function ($stack) use ($shape): void {
                $property = new ReflectionProperty(HandlerStack::class, 'stack');
                $entries  = $property->getValue($stack);
                $last     = array_key_last($entries);
                if ($shape === 'associative') {
                    $entries = array_combine(array_map(static fn ($i) => 'entry-' . $i, array_keys($entries)), $entries);
                } elseif ($shape === 'invalid-name') {
                    $entries[$last][1] = 42;
                } elseif ($shape === 'null-name') {
                    $entries[$last][1] = null;
                } elseif ($shape === 'invalid-callable') {
                    $entries[0][0] = new stdClass();
                } elseif ($shape === 'foreign-default') {
                    $entries[0][0] = new class {
                        public function __invoke(callable $next): callable
                        {
                            return $next;
                        }
                    };
                }
                $property->setValue($stack, $entries);
            },
        );
        if (!$supported) {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('configuration_invalid');
        }
        $binding = $factory->createTransport();
        if ($supported) {
            self::assertSame(0, $binding->driver->counters()['created']);
        }
    }

    public static function inventories(): iterable
    {
        yield 'associative inventory' => ['associative', false];
        yield 'non-string name' => ['invalid-name', false];
        yield 'nullable terminal name' => ['null-name', true];
        yield 'noncallable entry' => ['invalid-callable', false];
        yield 'callable object replaces SDK closure' => ['foreign-default', false];
    }

    private function factory(
        ?Closure $change = null,
        array $defaults = [],
        ?Closure $assertion = null,
    ): GuardedClientFactory {
        $config   = GuardConfig::fromArray([]);
        $clock    = new SystemClock();
        $registry = new PolicyRegistry($config, $clock);
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->expects(self::never())->method('resolve');
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            new NullDecisionReporter(),
        );
        $provider = new class ($change, $defaults, $assertion) implements ClientStackProviderInterface {
            public function __construct(private ?Closure $change, private array $defaults, private ?Closure $assertion) {}

            public function create(
                BoundaryMiddleware $boundary,
                TerminalGuardMiddleware $terminal,
            ): ClientStackConfiguration {
                $stack = HandlerStack::create();
                $stack->push($boundary, 'guard-boundary');
                $stack->push($terminal, 'guard-terminal');
                if ($this->change !== null) {
                    ($this->change)($stack, $boundary, $terminal);
                }

                return new ClientStackConfiguration($stack, $this->defaults, $this->assertion);
            }
        };

        return new GuardedClientFactory($engine, $config, $registry, $provider);
    }

    public function testTheReservedCorePrefixNameDoesNotPermitAForeignMiddleware(): void
    {
        $factory = $this->factory(
            static function ($stack): void {
                $stack->before('guard-boundary', static fn (callable $next): callable => $next, 'typo3_allowed_hosts');
            },
        );
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        $factory->createTransport();
    }
}
