<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit\Service;

use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\DecisionEvent;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\NrHttpGuard\Configuration\GlobalHttpDefaults;
use Netresearch\NrHttpGuard\Http\CoreStackProvider;
use Netresearch\NrHttpGuard\Http\MiddlewareRegistry;
use Netresearch\NrHttpGuard\Http\RawRequestFactoryRegistration;
use Netresearch\NrHttpGuard\Service\LibraryServiceFactory;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;

final class FactoryRecordingWriter implements WriterInterface
{
    /** @var list<LogRecord> */
    public array $records = [];

    public function writeLog(LogRecord $record): WriterInterface
    {
        $this->records[] = $record;

        return $this;
    }
}

final class LibraryServiceFactoryTest extends TestCase
{
    private mixed $originalGlobals;
    private bool $hadGlobals;

    protected function setUp(): void
    {
        $this->hadGlobals           = array_key_exists('TYPO3_CONF_VARS', $GLOBALS);
        $this->originalGlobals      = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        $GLOBALS['TYPO3_CONF_VARS'] = ['LOG' => []];
    }

    protected function tearDown(): void
    {
        if ($this->hadGlobals) {
            $GLOBALS['TYPO3_CONF_VARS'] = $this->originalGlobals;
        } else {
            unset($GLOBALS['TYPO3_CONF_VARS']);
        }
    }

    public function testReporterUsesLocalEnvironmentVerbatimAndSafeCoreLogLevels(): void
    {
        $name     = 'HTTP_GUARD_FACTORY_TEST_KEY';
        $original = getenv($name, true);
        try {
            foreach ([false, '', ' synthetic operator key '] as $key) {
                putenv($key === false ? $name : $name . '=' . $key);
                [$factory, $writer] = $this->factory();
                $config             = GuardConfig::fromArray(['logging' => ['allowedSampleRate' => 1, 'hostHmacKeyEnv' => $name]]);
                $reporter           = $factory->reporter($config, new SystemClock());
                $reporter->report($this->event('allow'));
                $reporter->report($this->event('deny'));
                self::assertCount(2, $writer->records);
                self::assertSame('info', $writer->records[0]->getLevel());
                self::assertSame('warning', $writer->records[1]->getLevel());
                foreach ($writer->records as $record) {
                    self::assertSame('Outbound HTTP policy decision', $record->getMessage());
                    $data = $record->getData();
                    if ($key === false || $key === '') {
                        self::assertArrayNotHasKey('host', $data);
                    } else {
                        self::assertSame(hash_hmac('sha256', 'api.example', $key), $data['host']);
                    }
                    foreach (['url', 'request', 'headers', 'query', 'path', 'hostHmacKey'] as $field) {
                        self::assertArrayNotHasKey($field, $data);
                    }
                }
                self::assertSame($key, getenv($name, true));
            }
        } finally {
            putenv($original === false ? $name : $name . '=' . $original);
        }
    }

    public function testReporterDoesNotReadUnconfiguredEnvironmentKey(): void
    {
        [$factory, $writer] = $this->factory();
        $factory->reporter(GuardConfig::fromArray([]), new SystemClock())->report($this->event('deny'));
        self::assertCount(1, $writer->records);
        self::assertArrayNotHasKey('host', $writer->records[0]->getData());
    }

    public function testCompositionSharesStaticResolverRegistryAndDecisionReporter(): void
    {
        [$factory, $writer] = $this->factory();
        $config             = GuardConfig::fromArray(
            ['resolver' => ['staticHosts' => ['api.example' => ['8.8.8.8']]], 'logging' => ['allowedSampleRate' => 1]],
        );
        $clock      = new SystemClock();
        $resolver   = $factory->resolver($config, $clock);
        $resolution = $resolver->resolve('api.example');
        self::assertSame('static', $resolution->source);
        self::assertSame(['8.8.8.8'], $resolution->addresses);
        $registry = $factory->registry($config, $clock);
        self::assertSame($config, $registry->configuration());
        $engine  = $factory->engine($resolver, $registry, $clock, $factory->reporter($config, $clock));
        $global  = $factory->globalFactory($engine, $config, $registry);
        $context = $global->middlewarePair()->context;
        self::assertSame($config->revision, $context->policyRevision);
        $plan = $engine->plan(new Request('GET', 'https://api.example/'), $context);
        self::assertSame(['8.8.8.8'], $plan->addresses);
        self::assertSame('info', $writer->records[0]->getLevel());
        $decision = $engine->evaluate(new Request('GET', 'http://127.0.0.1/'), $context);
        self::assertSame('deny', $decision->decision);
        self::assertSame('address_forbidden', $decision->reasonCode);
        self::assertSame('warning', $writer->records[1]->getLevel());
        $global->createTransport();
        $provider = new CoreStackProvider(
            new GuzzleClientFactory(),
            new MiddlewareRegistry(
                $global,
                $config,
                new GlobalHttpDefaults(),
                new RawRequestFactoryRegistration(static fn () => null, static fn () => null),
            ),
        );
        try {
            $factory->contextFactory($engine, $config, $registry, $provider)->createTransport();
            self::fail('Context factory omitted the Core registry validation');
        } catch (PolicyException $failure) {
            self::assertSame('configuration_invalid', $failure->reasonCode());
        }
    }

    public function testFactoryRejectsMismatchedConfigurationRegistry(): void
    {
        [$factory] = $this->factory();
        $config    = GuardConfig::fromArray([]);
        $clock     = new SystemClock();
        $registry  = $factory->registry($config, $clock);
        $engine    = $factory->engine($factory->resolver($config, $clock), $registry, $clock, $factory->reporter($config, $clock));
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Outbound HTTP policy: configuration_invalid');
        $factory->globalFactory($engine, GuardConfig::fromArray([]), $registry);
    }

    /** @return array{LibraryServiceFactory, FactoryRecordingWriter} */
    private function factory(): array
    {
        $manager = new LogManager();
        $logger  = $manager->getLogger('Netresearch.HttpGuard');
        self::assertInstanceOf(Logger::class, $logger);
        $writer = new FactoryRecordingWriter();
        $logger->addWriter('info', $writer);

        return [new LibraryServiceFactory($manager), $writer];
    }

    private function event(string $decision): DecisionEvent
    {
        return new DecisionEvent(
            1,
            '',
            'enforce',
            $decision,
            $decision === 'allow' ? null : 'address_forbidden',
            null,
            '',
            'ordinary_global_unicast',
            'https',
            443,
            'static',
            str_repeat('b', 24),
            'api.example',
        );
    }
}
