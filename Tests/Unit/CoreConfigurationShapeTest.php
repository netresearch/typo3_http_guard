<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use LogicException;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\Resolution;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\NrHttpGuard\Configuration\GlobalHttpDefaults;
use Netresearch\NrHttpGuard\Http\MiddlewareRegistry;
use Netresearch\NrHttpGuard\Http\RawRequestFactoryRegistration;
use Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TYPO3\CMS\Core\Http\RequestFactory;

final class CoreConfigurationShapeTest extends TestCase
{
    private mixed $original;
    private bool $existed;

    protected function setUp(): void
    {
        $this->existed  = array_key_exists('TYPO3_CONF_VARS', $GLOBALS);
        $this->original = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->existed) {
            $GLOBALS['TYPO3_CONF_VARS'] = $this->original;
        } else {
            unset($GLOBALS['TYPO3_CONF_VARS']);
        }
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function malformedShapes(): iterable
    {
        yield 'root string' => ['root', 'malformed'];
        yield 'root null' => ['root', null];
        yield 'SYS scalar' => ['SYS', false];
        yield 'Objects scalar' => ['Objects', 'malformed'];
        yield 'HTTP scalar' => ['HTTP', false];
        yield 'HTTP numeric option key' => ['HTTP', [0 => 'malformed']];
        yield 'handler scalar' => ['handler', false];
    }

    #[DataProvider('malformedShapes')]
    public function testMalformedConfigurationFailsClosed(string $section, mixed $value): void
    {
        $registry = $this->registry();
        if ($section === 'root') {
            $GLOBALS['TYPO3_CONF_VARS'] = $value;
        } elseif ($section === 'Objects') {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] = $value;
        } elseif ($section === 'handler') {
            $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $value;
        } else {
            $GLOBALS['TYPO3_CONF_VARS'][$section] = $value;
        }
        try {
            $registry->register();
            self::fail('Malformed global configuration reached middleware construction');
        } catch (PolicyException $exception) {
            self::assertSame('configuration_invalid', $exception->reasonCode());
        }
    }

    public function testValidRegistrationPreservesUnrelatedCoreValues(): void
    {
        $registry                                               = $this->registry();
        $GLOBALS['TYPO3_CONF_VARS']['unrelated']                = ['value' => 'preserved'];
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['unrelated_option'] = 'preserved';
        $registry->register();
        $registry->assertValid();
        self::assertSame(['value' => 'preserved'], $GLOBALS['TYPO3_CONF_VARS']['unrelated']);
        self::assertSame('preserved', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['unrelated_option']);
        self::assertSame(
            [MiddlewareRegistry::BOUNDARY, MiddlewareRegistry::TERMINAL],
            array_keys($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']),
        );
        self::assertSame(5, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allow_redirects']['max']);
    }

    public function testMalformedHttpAfterRegistrationCannotPassValidation(): void
    {
        $registry = $this->registry();
        $registry->register();
        $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = false;
        try {
            $registry->assertValid();
            self::fail('Malformed post-registration HTTP configuration accepted');
        } catch (PolicyException $exception) {
            self::assertSame('configuration_invalid', $exception->reasonCode());
        }
    }

    private function registry(): MiddlewareRegistry
    {
        $replacement                = RequestFactoryCompatibility::replacementClass();
        $factory                    = (new ReflectionClass($replacement))->newInstanceWithoutConstructor();
        $lookup                     = static fn (): object => $factory;
        $raw                        = new RawRequestFactoryRegistration($lookup, $lookup);
        $GLOBALS['TYPO3_CONF_VARS'] = ['SYS' => ['Objects' => [RequestFactory::class => ['className' => $replacement]]], 'HTTP' => ['handler' => []]];
        $raw->assertValid();
        $config   = GuardConfig::fromArray([]);
        $clock    = new SystemClock();
        $policy   = new PolicyRegistry($config, $clock);
        $resolver = new class implements ResolverInterface {
            public function resolve(string $canonicalHost): Resolution
            {
                throw new LogicException('Configuration controls must not resolve DNS');
            }
        };
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $policy,
            $clock,
            new NullDecisionReporter(),
        );

        return new MiddlewareRegistry(
            new GuardedClientFactory($engine, $config, $policy),
            $config,
            new GlobalHttpDefaults(),
            $raw,
        );
    }
}
