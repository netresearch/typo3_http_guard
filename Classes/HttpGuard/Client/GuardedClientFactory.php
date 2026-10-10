<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Client;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Netresearch\HttpGuard\EndpointClientFactoryInterface;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\PublicFetchClientInterface;
use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\GuardedTransferFactory;
use Netresearch\HttpGuard\Transport\InvocationRegistry;
use Netresearch\HttpGuard\Transport\TerminalGuardMiddleware;
use Netresearch\HttpGuard\Transport\TransferDriver;
use Psr\Http\Client\ClientInterface;
use ReflectionFunction;
use ReflectionProperty;

final readonly class GuardedClientFactory implements EndpointClientFactoryInterface
{
    public function __construct(
        private PolicyEngine $engine,
        private GuardConfig $config,
        private PolicyRegistry $registry,
        private ?ClientStackProviderInterface $stackProvider = null,
    ) {
        if ($registry->configuration() !== $config) {
            throw new PolicyException('configuration_invalid');
        }
    }

    public function middlewarePair(?string $endpointId = null, bool $publicFetch = false): MiddlewarePair
    {
        $context     = $this->registry->newContext($endpointId);
        $driver      = new TransferDriver();
        $invocations = new InvocationRegistry();
        $boundary    = new BoundaryMiddleware($this->engine, $this->config, $this->registry, $context, $invocations, $publicFetch);
        $terminal    = new TerminalGuardMiddleware(
            $this->engine,
            $this->config,
            $context,
            $invocations,
            new GuardedTransferFactory($this->engine, $context, $driver),
        );

        return new MiddlewarePair($boundary, $terminal, $driver, $context);
    }

    /** @param array<string,mixed> $defaultOptions */
    public function createTransport(array $defaultOptions = [], ?string $endpointId = null): GuardedClientBinding
    {
        return $this->build($defaultOptions, $endpointId, false);
    }

    public function forEndpoint(string $configuredEndpointId): ClientInterface
    {
        return new Psr18Client($this->createTransport([], $configuredEndpointId));
    }

    public function publicClient(): ClientInterface
    {
        return new Psr18Client($this->createTransport());
    }

    public function publicFetch(): PublicFetchClientInterface
    {
        return new PublicFetchClient($this->build([], null, true), $this->config->data['redirects']['max']);
    }

    /** @param array<string,mixed> $options */
    private function build(array $options, ?string $endpointId, bool $publicFetch): GuardedClientBinding
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
            if (array_key_exists($key, $options) && $this->config->mode === 'enforce') {
                throw new PolicyException('option_forbidden');
            }
        }
        $pair     = $this->middlewarePair($endpointId, $publicFetch);
        $provider = ($this->stackProvider ?? new DefaultStackProvider())->create($pair->boundary, $pair->terminal);
        $entries  = self::stackEntries($provider->stack);
        self::assertPosition($entries, $pair->boundary, $pair->terminal);
        $assertion = static function () use ($provider, $pair, $entries): void {
            ($provider->registryAssertion ?? static function (): void {})();
            if (self::stackEntries($provider->stack) !== $entries) {
                throw new PolicyException('configuration_invalid');
            }
            self::assertPosition($entries, $pair->boundary, $pair->terminal);
        };
        $pair->boundary->setRegistryAssertion($assertion);
        $pair->terminal->setRegistryAssertion($assertion);
        $profile  = $this->config->mode === 'enforce' ? $this->registry->validateContext($pair->context) : null;
        $defaults = $provider->defaultOptions;
        unset($defaults['handler'], $defaults['allowed_hosts']);
        if ($publicFetch) {
            foreach ([
                'auth',
                'cookies',
                'cert',
                'ssl_key',
                'cert_type',
                'ssl_key_type',
                'headers',
                'body',
                'json',
                'form_params',
                'multipart',
                'query',
                'base_uri',
                'sink',
            ] as $key) {
                unset($defaults[$key]);
            }
            $defaults['cookies'] = false;
            $defaults['headers'] = [];
        }
        if ($this->config->mode === 'enforce' && !array_key_exists('allow_redirects', $defaults)) {
            $defaults['allow_redirects'] = $profile instanceof \Netresearch\HttpGuard\EndpointProfile && $profile->redirects === 'none' ? false : ['max' => $this->config->data['redirects']['max'], 'strict' => false, 'protocols' => ['http', 'https']];
        }
        if ($this->config->mode === 'enforce') {
            $defaults['idn_conversion'] = false;
        }
        $config            = array_replace($defaults, $options);
        $config['handler'] = $provider->stack;

        return new GuardedClientBinding(new Client($config), $pair->driver, $pair->context);
    }

    /**
     * @param HandlerStack<covariant callable(\Psr\Http\Message\RequestInterface, array<array-key,mixed>): \GuzzleHttp\Promise\PromiseInterface> $stack
     *
     * @return list<array{callable(mixed...):mixed,string|null}>
     */
    private static function stackEntries(HandlerStack $stack): array
    {
        // This private inventory is version-bound by RuntimeSupport and the G0 probes.
        $entries = (new ReflectionProperty(HandlerStack::class, 'stack'))->getValue($stack);
        if (!is_array($entries) || !array_is_list($entries)) {
            throw new PolicyException('configuration_invalid');
        }
        $inventory = [];
        foreach ($entries as $entry) {
            if (!is_array($entry) || !array_is_list($entry) || count($entry) !== 2 || !is_callable($entry[0]) || $entry[1] !== null && !is_string($entry[1])) {
                throw new PolicyException('configuration_invalid');
            }
            $inventory[] = [$entry[0], $entry[1]];
        }

        return $inventory;
    }

    /** @param list<array{callable(mixed...):mixed,string|null}> $entries */
    private static function assertPosition(
        array $entries,
        BoundaryMiddleware $boundary,
        TerminalGuardMiddleware $terminal,
    ): void {
        $boundaries    = 0;
        $terminals     = 0;
        $boundaryIndex = null;
        $terminalIndex = null;
        foreach ($entries as $index => $entry) {
            if ($entry[0] instanceof BoundaryMiddleware) {
                ++$boundaries;
                if ($entry[0] === $boundary) {
                    $boundaryIndex = $index;
                }
            }
            if ($entry[0] instanceof TerminalGuardMiddleware) {
                ++$terminals;
                if ($entry[0] === $terminal) {
                    $terminalIndex = $index;
                }
            }
        }
        if ($boundaries !== 1 || $terminals !== 1 || $boundaryIndex === null || $terminalIndex !== array_key_last($entries) || $boundaryIndex >= $terminalIndex) {
            throw new PolicyException('configuration_invalid');
        }
        $defaults = self::stackEntries(
            HandlerStack::create(
                static function (): never {
                    throw new PolicyException('configuration_invalid');
                },
            ),
        );
        $prefix = array_slice($entries, 0, $boundaryIndex);
        // The supported Core wrapper remains cumulative before our boundary.
        if (count($prefix) === count($defaults) + 1) {
            $last = $prefix[array_key_last($prefix)];
            if ($last[1] !== 'typo3_allowed_hosts' || !is_object($last[0]) || strcmp($last[0]::class, 'TYPO3\CMS\Core\Http\Client\AllowedHostsMiddleware') !== 0) {
                throw new PolicyException('configuration_invalid');
            }
            array_pop($prefix);
        }
        if (count($prefix) !== count($defaults)) {
            throw new PolicyException('configuration_invalid');
        }
        foreach ($defaults as $index => $expected) {
            $actual = $prefix[$index];
            if ($actual[1] !== $expected[1] || !$actual[0] instanceof Closure || !$expected[0] instanceof Closure) {
                throw new PolicyException('configuration_invalid');
            }
            $a = new ReflectionFunction($actual[0]);
            $b = new ReflectionFunction($expected[0]);
            if ([$a->getFileName(), $a->getStartLine(), $a->getEndLine()] !== [$b->getFileName(), $b->getStartLine(), $b->getEndLine()]) {
                throw new PolicyException('configuration_invalid');
            }
        }
    }
}
