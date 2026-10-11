<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\NrHttpGuard\Http\CoreStackProvider;
use Netresearch\NrHttpGuard\Http\MiddlewareRegistry;
use Netresearch\NrHttpGuard\Tests\Fixtures\AdapterRuntimeFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

final class CoreStackProviderContractTest extends TestCase
{
    private ?AdapterRuntimeFixture $fixture = null;

    protected function tearDown(): void
    {
        $this->fixture?->restore();
    }

    public function testContextProviderCopiesTheCoreStackAndReplacesOnlyItsOwnGuards(): void
    {
        $foreign       = static fn (callable $next): callable => $next;
        $this->fixture = new AdapterRuntimeFixture(
            ['mode' => 'disabled'],
            ['handler' => ['foreign' => $foreign], 'custom-sdk-field' => ['kept' => true], 'timeout' => 3.5],
        );
        $client = (new GuzzleClientFactory())->getClient('fixture-context');
        self::assertInstanceOf(Client::class, $client);
        $originalStack = $client->getConfig('handler');
        self::assertInstanceOf(HandlerStack::class, $originalStack);
        $before = $this->entries($originalStack);
        $core   = $this->createMock(GuzzleClientFactory::class);
        $core->expects(self::once())->method('getClient')->with('fixture-context')->willReturn($client);
        $pair     = $this->fixture->factory->middlewarePair();
        $provided = (new CoreStackProvider($core, $this->fixture->middleware, 'fixture-context'))->create(
            $pair->boundary,
            $pair->terminal,
        );
        self::assertNotSame($originalStack, $provided->stack);
        self::assertSame($before, $this->entries($originalStack));
        self::assertSame($provided->stack, $provided->defaultOptions['handler']);
        self::assertSame(['kept' => true], $provided->defaultOptions['custom-sdk-field']);
        self::assertSame(3.5, $provided->defaultOptions['timeout']);
        $guards = [];
        foreach ($this->entries($provided->stack) as $entry) {
            $guards[$entry[1]][] = $entry[0];
        }
        self::assertSame([$pair->boundary], $guards[MiddlewareRegistry::BOUNDARY]);
        self::assertSame([$pair->terminal], $guards[MiddlewareRegistry::TERMINAL]);
        self::assertSame([$foreign], $guards['foreign']);
        $provided->stack->remove($pair->boundary);
        $provided->stack->remove($pair->terminal);
        $names = array_column($this->entries($provided->stack), 1);
        self::assertNotContains(MiddlewareRegistry::BOUNDARY, $names);
        self::assertNotContains(MiddlewareRegistry::TERMINAL, $names);
        self::assertSame($before, $this->entries($originalStack));
    }

    #[DataProvider('invalidClientShapes')]
    public function testProviderRejectsUnsupportedClientShapesBeforeAnyRequest(string $shape): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $client        = $shape === 'foreign-client' ? $this->createStub(ClientInterface::class) : new Client(['handler' => static fn () => Create::promiseFor(new Response(204))]);
        $core          = $this->createMock(GuzzleClientFactory::class);
        $core->expects(self::once())->method('getClient')->willReturn($client);
        $pair = $this->fixture->factory->middlewarePair();
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        (new CoreStackProvider($core, $this->fixture->middleware))->create($pair->boundary, $pair->terminal);
    }

    /** @return iterable<string,array{string}> */
    public static function invalidClientShapes(): iterable
    {
        yield 'interface client without concrete native SDK configuration' => ['foreign-client'];
        yield 'SDK client with a callable leaf instead of stack' => ['nonstack-handler'];
    }

    #[DataProvider('assertionSites')]
    public function testProviderAndBothInjectedGuardsRetainLiveRegistryAssertions(string $site): void
    {
        $this->fixture = new AdapterRuntimeFixture(['mode' => 'disabled']);
        $pair          = $this->fixture->factory->middlewarePair();
        $provided      = (new CoreStackProvider(new GuzzleClientFactory(), $this->fixture->middleware))->create(
            $pair->boundary,
            $pair->terminal,
        );
        unset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'][MiddlewareRegistry::TERMINAL]);
        $calls = 0;
        try {
            if ($site === 'provider') {
                self::assertNotNull($provided->registryAssertion);
                ($provided->registryAssertion)();
            } else {
                $next = static function () use (&$calls) {
                    ++$calls;

                    return Create::promiseFor(new Response(204));
                };
                $guard = $site === 'boundary' ? $pair->boundary : $pair->terminal;
                $guard($next)(new Request('GET', 'https://8.8.8.8/'), [])->wait();
            }
            self::fail('Context provider lost registry validation');
        } catch (PolicyException $failure) {
            self::assertSame('configuration_invalid', $failure->reasonCode());
        }
        self::assertSame(0, $calls);
    }

    /** @return iterable<string,array{string}> */
    public static function assertionSites(): iterable
    {
        yield 'returned assertion' => ['provider'];
        yield 'injected boundary' => ['boundary'];
        yield 'injected terminal' => ['terminal'];
    }

    /** @return array<array-key,mixed> */
    private function entries(HandlerStack $stack): array
    {
        $entries = (new ReflectionProperty(HandlerStack::class, 'stack'))->getValue($stack);
        self::assertIsArray($entries);

        return $entries;
    }

    public function testActualCoreStackPassesTheKernelTransportPositionContractWithoutSending(): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $factory       = new GuardedClientFactory(
            $this->fixture->engine,
            $this->fixture->config,
            $this->fixture->policy,
            new CoreStackProvider(new GuzzleClientFactory(), $this->fixture->middleware),
        );
        $binding = $factory->createTransport();
        self::assertInstanceOf(Client::class, $binding->client);
        self::assertSame('enforce', $this->fixture->config->mode);
    }
}
