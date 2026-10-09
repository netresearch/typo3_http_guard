<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\NrHttpGuard\Http\MiddlewareRegistry;
use Netresearch\NrHttpGuard\Tests\Fixtures\AdapterRuntimeFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use TYPO3\CMS\Core\Http\RequestFactory;

final class MiddlewareRegistryContractTest extends TestCase
{
    private ?AdapterRuntimeFixture $fixture = null;

    protected function tearDown(): void
    {
        $this->fixture?->restore();
    }

    public function testRegistrationKeepsForeignMiddlewareOrderAndBindingIdentity(): void
    {
        $first         = static fn (callable $next): callable => $next;
        $second        = static fn (callable $next): callable => $next;
        $this->fixture = new AdapterRuntimeFixture([], ['handler' => ['foreign-first' => $first, 4 => $second]]);
        $pair          = $this->fixture->middleware->binding();
        $registry      = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'];
        self::assertSame(
            [MiddlewareRegistry::BOUNDARY, 'foreign-first', 4, MiddlewareRegistry::TERMINAL],
            array_keys($registry),
        );
        self::assertSame($pair->boundary, $registry[MiddlewareRegistry::BOUNDARY]);
        self::assertSame($pair->terminal, $registry[MiddlewareRegistry::TERMINAL]);
        self::assertSame($first, $registry['foreign-first']);
        self::assertSame($second, $registry[4]);
        self::assertSame($pair, $this->fixture->middleware->binding());
        $this->expectException(PolicyException::class);
        $this->fixture->middleware->register();
    }

    #[DataProvider('invalidInitialHandlers')]
    public function testMalformedForeignHandlersFailBeforeChangingGlobalDefaults(string $kind): void
    {
        $this->fixture = new AdapterRuntimeFixture(register: false);
        $pair          = $this->fixture->factory->middlewarePair();
        $invalid       = match ($kind) {
            'nonarray'          => false,
            'noncallable'       => ['foreign' => new stdClass()],
            'reserved-boundary' => [MiddlewareRegistry::BOUNDARY => null],
            'reserved-terminal' => [MiddlewareRegistry::TERMINAL => null],
            'foreign-boundary'  => ['foreign' => $pair->boundary],
            'foreign-terminal'  => ['foreign' => $pair->terminal],
        };
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $invalid;
        $before                                        = $GLOBALS['TYPO3_CONF_VARS'];
        try {
            $this->fixture->middleware->register();
            self::fail('Invalid registration accepted');
        } catch (PolicyException $failure) {
            self::assertSame('configuration_invalid', $failure->reasonCode());
        }
        self::assertSame($before, $GLOBALS['TYPO3_CONF_VARS']);
    }

    /** @return iterable<string,array{string}> */
    public static function invalidInitialHandlers(): iterable
    {
        foreach (['nonarray', 'noncallable', 'reserved-boundary', 'reserved-terminal', 'foreign-boundary', 'foreign-terminal'] as $kind) {
            yield $kind => [$kind];
        }
    }

    #[DataProvider('registryDrifts')]
    public function testEachProtectedBindingInvariantRejectsLaterRegistryDrift(string $kind): void
    {
        $foreign       = static fn (callable $next): callable => $next;
        $this->fixture = new AdapterRuntimeFixture([], ['handler' => ['foreign' => $foreign]]);
        $pair          = $this->fixture->middleware->binding();
        $registry      = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'];
        $other         = $this->fixture->factory->middlewarePair();
        switch ($kind) {
            case 'boundary-reordered':
                $registry = [
                    'foreign'                    => $foreign,
                    MiddlewareRegistry::BOUNDARY => $pair->boundary,
                    MiddlewareRegistry::TERMINAL => $pair->terminal,
                ];
                break;
            case 'terminal-reordered':
                $registry = [
                    MiddlewareRegistry::BOUNDARY => $pair->boundary,
                    MiddlewareRegistry::TERMINAL => $pair->terminal,
                    'foreign'                    => $foreign,
                ];
                break;
            case 'boundary-replaced':
                $registry[MiddlewareRegistry::BOUNDARY] = $other->boundary;
                break;
            case 'terminal-replaced':
                $registry[MiddlewareRegistry::TERMINAL] = $other->terminal;
                break;
            case 'duplicate-boundary':
                $registry['foreign'] = $other->boundary;
                break;
            case 'duplicate-terminal':
                $registry['foreign'] = $other->terminal;
                break;
            case 'noncallable':
                $registry['foreign'] = false;
                break;
            case 'empty':
                $registry = [];
                break;
            case 'scalar':
                $registry = false;
                break;
        }
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $registry;
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        $this->fixture->middleware->binding();
    }

    /** @return iterable<string,array{string}> */
    public static function registryDrifts(): iterable
    {
        foreach ([
            'boundary-reordered',
            'terminal-reordered',
            'boundary-replaced',
            'terminal-replaced',
            'duplicate-boundary',
            'duplicate-terminal',
            'noncallable',
            'empty',
            'scalar',
        ] as $kind) {
            yield $kind => [$kind];
        }
    }

    public function testRawFactoryMappingMustRemainValidOnEveryBindingLookup(): void
    {
        $this->fixture                                                                    = new AdapterRuntimeFixture();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][RequestFactory::class]['className'] = RequestFactory::class;
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        $this->fixture->middleware->binding();
    }

    public function testInvalidRawMappingCannotPartiallyInstallMiddleware(): void
    {
        $this->fixture = new AdapterRuntimeFixture(register: false);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][RequestFactory::class]);
        $before = $GLOBALS['TYPO3_CONF_VARS'];
        try {
            $this->fixture->middleware->register();
            self::fail('Invalid raw binding accepted');
        } catch (PolicyException $failure) {
            self::assertSame('configuration_invalid', $failure->reasonCode());
        }
        self::assertSame($before, $GLOBALS['TYPO3_CONF_VARS']);
    }

    #[DataProvider('guardSides')]
    public function testBothRegisteredMiddlewareCallbacksRevalidateBeforeAnOfflineLeaf(string $side): void
    {
        // Disabled-mode leaf is explicitly offline, even when the assertion is removed by a mutant.
        $this->fixture = new AdapterRuntimeFixture(['mode' => 'disabled']);
        $pair          = $this->fixture->middleware->binding();
        unset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'][MiddlewareRegistry::TERMINAL]);
        $calls = 0;
        $next  = static function () use (&$calls) {
            ++$calls;

            return Create::promiseFor(new Response(204));
        };
        $middleware = $side === 'boundary' ? $pair->boundary : $pair->terminal;
        try {
            $middleware($next)(new Request('GET', 'https://8.8.8.8/'), [])->wait();
            self::fail('Registry assertion omitted');
        } catch (PolicyException $failure) {
            self::assertSame('configuration_invalid', $failure->reasonCode());
        }
        self::assertSame(0, $calls);
    }

    /** @return iterable<string,array{string}> */
    public static function guardSides(): iterable
    {
        yield 'boundary' => ['boundary'];
        yield 'terminal' => ['terminal'];
    }

    #[DataProvider('reservedCallableNames')]
    public function testReservedCallableNamesFailBeforeDefaultsAreChanged(string $name): void
    {
        $this->fixture                                 = new AdapterRuntimeFixture(register: false);
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = [$name => static fn (callable $next): callable => $next];
        $before                                        = $GLOBALS['TYPO3_CONF_VARS'];
        try {
            $this->fixture->middleware->register();
            self::fail('A reserved name cannot be repurposed even when callable');
        } catch (PolicyException $failure) {
            self::assertSame('configuration_invalid', $failure->reasonCode());
        }
        self::assertSame($before, $GLOBALS['TYPO3_CONF_VARS']);
    }

    /** @return iterable<string,array{string}> */
    public static function reservedCallableNames(): iterable
    {
        yield 'boundary name' => [MiddlewareRegistry::BOUNDARY];
        yield 'terminal name' => [MiddlewareRegistry::TERMINAL];
    }
}
