<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Fixtures;

use Closure;
use DateTimeImmutable;
use LogicException;
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\ClockInterface;
use Netresearch\HttpGuard\DnsAnswer;
use Netresearch\HttpGuard\DnsQueryInterface;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\StaticThenDnsResolver;
use Netresearch\NrHttpGuard\Configuration\ConfigurationLoader;
use Netresearch\NrHttpGuard\Configuration\GlobalHttpDefaults;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticBootState;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticsService;
use Netresearch\NrHttpGuard\Diagnostics\LegacyConfigurationInventory;
use Netresearch\NrHttpGuard\Http\MiddlewareRegistry;
use Netresearch\NrHttpGuard\Http\RawRequestFactoryRegistration;
use Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility;
use Netresearch\NrHttpGuard\Service\LibraryServiceFactory;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;

/** Real adapter/Core composition; callers never send an HTTP request through this fixture. */
final class AdapterRuntimeFixture
{
    public readonly ConfigurationLoader $loader;
    public readonly GuardConfig $config;
    public readonly ClockInterface $clock;
    public readonly LibraryServiceFactory $library;
    public readonly PolicyEngine $engine;
    public readonly PolicyRegistry $policy;
    public readonly GuardedClientFactory $factory;
    public readonly MiddlewareRegistry $middleware;
    public readonly DiagnosticBootState $boot;
    public readonly DiagnosticsService $diagnostics;
    public readonly RequestFactory $guarded;
    public readonly RawRequestFactoryRegistration $registration;
    private readonly mixed $originalGlobals;
    private readonly bool $hadGlobals;
    private readonly mixed $originalServerProxy;
    private readonly bool $hadServerProxy;
    /** @var array<string,string> */
    private array $proxyEnvironment = [];

    /** @param array<string,mixed> $extension @param array<string,mixed> $http */
    public function __construct(array $extension = [], array $http = [], bool $register = true)
    {
        $this->hadGlobals          = array_key_exists('TYPO3_CONF_VARS', $GLOBALS);
        $this->originalGlobals     = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        $this->hadServerProxy      = array_key_exists('HTTP_PROXY', $_SERVER);
        $this->originalServerProxy = $_SERVER['HTTP_PROXY'] ?? null;
        unset($_SERVER['HTTP_PROXY']);
        foreach (getenv(null, true) as $name => $value) {
            if (is_string($name) && in_array(strtolower($name), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                $this->proxyEnvironment[$name] = $value;
                putenv($name);
            }
        }
        $extension = array_replace_recursive(
            ['resolver' => ['staticHosts' => ['api.example' => ['8.8.8.8'], 'erp.test' => ['10.23.5.12']]]],
            $extension,
        );
        $replacement                = RequestFactoryCompatibility::replacementClass();
        $GLOBALS['TYPO3_CONF_VARS'] = [
            'LOG'     => [],
            'SYS'     => ['Objects' => [RequestFactory::class => ['className' => $replacement]]],
            'HTTP'    => array_replace(['verify' => true, 'allowed_hosts' => [], 'handler' => []], $http),
            'EXTCONF' => ['nr_http_guard' => $extension],
        ];
        $this->loader = new ConfigurationLoader();
        $this->config = $this->loader->load();
        $this->clock  = new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-10-09T12:00:00Z');
            }

            public function monotonic(): float
            {
                return 1000.0;
            }
        };
        $this->library = new LibraryServiceFactory(new LogManager());
        $this->policy  = $this->library->registry($this->config, $this->clock);
        $this->engine  = $this->library->engine(
            new StaticThenDnsResolver(
                $this->config,
                new class (fn (): int => ++$this->dnsQueries) implements DnsQueryInterface {
                    public function __construct(private readonly Closure $onQuery) {}

                    public function query(string $absoluteFqdn, int $qtype): DnsAnswer
                    {
                        ($this->onQuery)();
                        throw new LogicException('Offline adapter contracts must not perform DNS I/O');
                    }
                },
                $this->clock,
            ),
            $this->policy,
            $this->clock,
            new NullDecisionReporter(),
        );
        $this->factory = $this->library->globalFactory($this->engine, $this->config, $this->policy);
        $guarded       = null;
        $lookup        = static function () use (&$guarded): ?RequestFactory {
            return $guarded;
        };
        $this->registration = new RawRequestFactoryRegistration($lookup, $lookup);
        $core               = new GuzzleClientFactory();
        $guarded            = new $replacement(
            $core,
            $this->loader,
            $this->registration,
            $this->engine,
            $this->policy,
            new RequestFactory($core),
        );
        $this->guarded    = $guarded;
        $this->middleware = new MiddlewareRegistry($this->factory, $this->config, new GlobalHttpDefaults(), $this->registration);
        if ($register) {
            $this->middleware->register();
        }
        $this->boot        = new DiagnosticBootState();
        $this->diagnostics = new DiagnosticsService(
            $this->loader,
            $this->boot,
            new LegacyConfigurationInventory(),
            fn (): MiddlewareRegistry => $this->middleware,
            fn (): PolicyEngine => $this->engine,
            fn (): PolicyRegistry => $this->policy,
            $this->clock,
        );
    }

    /** @return array<string,mixed> */
    public static function endpoint(?string $reviewAfter = null): array
    {
        return [
            'origin'       => 'https://erp.test',
            'allowedCidrs' => ['10.23.5.12/32'],
            'methods'      => ['GET', 'POST'],
            'purpose'      => 'Offline contract fixture',
            'owner'        => 'Test maintainers',
            'reviewAfter'  => $reviewAfter,
        ];
    }

    public function restore(): void
    {
        if ($this->hadGlobals) {
            $GLOBALS['TYPO3_CONF_VARS'] = $this->originalGlobals;
        } else {
            unset($GLOBALS['TYPO3_CONF_VARS']);
        }
        if ($this->hadServerProxy) {
            $_SERVER['HTTP_PROXY'] = $this->originalServerProxy;
        } else {
            unset($_SERVER['HTTP_PROXY']);
        }
        foreach (getenv(null, true) as $name => $value) {
            if (is_string($name) && in_array(strtolower($name), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                putenv($name);
            }
        }
        foreach ($this->proxyEnvironment as $name => $value) {
            putenv($name . '=' . $value);
        }
    }
    public int $dnsQueries = 0;
}
