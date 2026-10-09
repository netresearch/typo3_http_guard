<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Transport;

use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\HandlerStack;
use Netresearch\HttpGuard\PolicyException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/** Reconstructs the controlled leaf's options; no caller routing state survives. */
final readonly class OptionSanitizer
{
    private const KNOWN = [
        'handler',
        'allow_redirects',
        'cookies',
        'http_errors',
        'decode_content',
        'verify',
        'idn_conversion',
        'timeout',
        'connect_timeout',
        'synchronous',
        'on_headers',
        'on_stats',
        'on_trailers',
        'progress',
        'sink',
        'cert',
        'ssl_key',
        'cert_type',
        'ssl_key_type',
        'force_ip_resolve',
        'expect',
        'stream',
        'delay',
        'proxy',
        'auth',
        'request_factory',
        'response_factory',
        'stream_factory',
        'uri_factory',
        'multiplex',
        '__redirect_count',
        '__guzzle_digest_retries',
        'protocols',
        'retries',
        'version',
        'base_uri',
        'curl',
        'debug',
    ];
    private const LEAF = [
        'timeout',
        'connect_timeout',
        'decode_content',
        'verify',
        'on_headers',
        'on_stats',
        'on_trailers',
        'progress',
        'sink',
        'cert',
        'ssl_key',
        'cert_type',
        'ssl_key_type',
        'force_ip_resolve',
        'expect',
        'request_factory',
        'response_factory',
        'stream_factory',
        'uri_factory',
        'multiplex',
        'protocols',
    ];

    public function __construct(private int $maxRedirects = 5, private bool $requireVerification = false) {}

    /**
     * @param array<array-key,mixed>                                                                                                 $options
     * @param HandlerStack<covariant callable(RequestInterface, array<array-key, mixed>): \GuzzleHttp\Promise\PromiseInterface>|null $expectedHandler
     *
     * @return array<string,mixed>
     */
    public function sanitize(
        RequestInterface $request,
        array $options = [],
        ?HandlerStack $expectedHandler = null,
    ): array {
        $this->assertKnownOptions($options);
        if (array_key_exists('handler', $options) && (!$expectedHandler instanceof HandlerStack || $options['handler'] !== $expectedHandler)) {
            throw new PolicyException('option_forbidden');
        }
        if ($expectedHandler instanceof HandlerStack && ($options['handler'] ?? null) !== $expectedHandler) {
            throw new PolicyException('option_forbidden');
        }
        $this->assertNoProxy($options);
        $this->assertRedirects($options['allow_redirects'] ?? false);
        foreach (['timeout', 'connect_timeout'] as $key) {
            if (array_key_exists($key, $options) && !$this->nonnegativeNumber($options[$key])) {
                throw new PolicyException('option_forbidden');
            }
        }
        if (($options['stream'] ?? false) !== false || ($options['delay'] ?? 0) !== 0 && ($options['delay'] ?? 0) !== 0.0 || ($options['debug'] ?? false) !== false || ($options['idn_conversion'] ?? false) !== false) {
            throw new PolicyException('option_forbidden');
        }
        foreach (['synchronous', 'http_errors'] as $key) {
            if (array_key_exists($key, $options) && !is_bool($options[$key])) {
                throw new PolicyException('option_forbidden');
            }
        }
        foreach (['on_headers', 'on_stats', 'on_trailers', 'progress'] as $key) {
            if (array_key_exists($key, $options) && !is_callable($options[$key])) {
                throw new PolicyException('option_forbidden');
            }
        }
        $verify = array_key_exists('verify', $options) ? $options['verify'] : true;
        if (!is_bool($verify) && (!is_string($verify) || !is_readable($verify)) || $this->requireVerification && $verify === false) {
            throw new PolicyException('option_forbidden');
        }
        foreach (['cert', 'ssl_key'] as $key) {
            if (array_key_exists($key, $options)) {
                $value = $options[$key];
                if (is_array($value)) {
                    if (!array_is_list($value) || count($value) !== 2 || !is_string($value[0]) || !is_string($value[1])) {
                        throw new PolicyException('option_forbidden');
                    }
                    $value = $value[0];
                }
                if (!is_string($value) || !is_readable($value)) {
                    throw new PolicyException('option_forbidden');
                }
            }
        }
        foreach (['cert_type', 'ssl_key_type'] as $key) {
            if (isset($options[$key]) && !in_array($options[$key], ['PEM', 'DER', 'P12'], true)) {
                throw new PolicyException('option_forbidden');
            }
        }
        if (isset($options['sink']) && !is_string($options['sink']) && !is_resource($options['sink']) && !$options['sink'] instanceof StreamInterface) {
            throw new PolicyException('option_forbidden');
        }
        if (isset($options['decode_content']) && !is_bool($options['decode_content']) && !is_string($options['decode_content'])) {
            throw new PolicyException('option_forbidden');
        }
        if (isset($options['expect']) && !is_bool($options['expect']) && !$this->nonnegativeNumber($options['expect'])) {
            throw new PolicyException('option_forbidden');
        }
        if (isset($options['force_ip_resolve']) && !in_array($options['force_ip_resolve'], ['v4', 'v6'], true)) {
            throw new PolicyException('option_forbidden');
        }
        if (!in_array($request->getProtocolVersion(), ['1.0', '1.1', '2', '2.0'], true) || array_key_exists('version', $options) && (!(is_string($options['version']) || is_int($options['version']) || is_float($options['version'])) || !in_array((string) $options['version'], ['1.0', '1.1', '2', '2.0'], true))) {
            throw new PolicyException('option_forbidden');
        }
        $cookies = $options['cookies'] ?? false;
        if ($cookies !== false && !$cookies instanceof CookieJarInterface) {
            throw new PolicyException('option_forbidden');
        }
        if (isset($options['protocols'])) {
            if (!$this->webProtocols($options['protocols'])) {
                throw new PolicyException('option_forbidden');
            }
        }
        if (isset($options['multiplex']) && !in_array($options['multiplex'], ['none', 'eager', 'wait', 'require_eager', 'require_wait'], true)) {
            throw new PolicyException('option_forbidden');
        }
        foreach ([
            'request_factory'  => \Psr\Http\Message\RequestFactoryInterface::class,
            'response_factory' => \Psr\Http\Message\ResponseFactoryInterface::class,
            'stream_factory'   => \Psr\Http\Message\StreamFactoryInterface::class,
            'uri_factory'      => \Psr\Http\Message\UriFactoryInterface::class,
        ] as $key => $interface) {
            if (isset($options[$key]) && !$options[$key] instanceof $interface) {
                throw new PolicyException('option_forbidden');
            }
        }
        foreach (['__redirect_count' => $this->maxRedirects, '__guzzle_digest_retries' => 2, 'retries' => 1000] as $key => $max) {
            if (isset($options[$key]) && (!is_int($options[$key]) || $options[$key] < 0 || $options[$key] > $max)) {
                throw new PolicyException('option_forbidden');
            }
        }
        $managedAuth       = $this->managedAuthentication($options);
        $leaf              = array_intersect_key($options, array_flip(self::LEAF));
        $leaf['proxy']     = '';
        $leaf['protocols'] = $options['protocols'] ?? ['http', 'https'];
        if ($managedAuth !== []) {
            $leaf['curl'] = $managedAuth;
        }

        return $leaf;
    }

    public function assertRedirects(mixed $redirects): void
    {
        if ($redirects === false) {
            return;
        }
        if ($redirects !== true && !is_array($redirects)) {
            throw new PolicyException('redirect_forbidden');
        }
        $settings = is_array($redirects) ? $redirects : [];
        $known    = ['max', 'strict', 'referer', 'protocols', 'on_redirect', 'track_redirects'];
        if (array_diff(array_keys($settings), $known) !== []) {
            throw new PolicyException('redirect_forbidden');
        }
        $max = $settings['max'] ?? 5;
        if (!is_int($max) || $max < 0 || $max > $this->maxRedirects) {
            throw new PolicyException('redirect_forbidden');
        }
        foreach (['strict', 'referer', 'track_redirects'] as $key) {
            if (isset($settings[$key]) && !is_bool($settings[$key])) {
                throw new PolicyException('redirect_forbidden');
            }
        }
        if (isset($settings['on_redirect']) && !is_callable($settings['on_redirect'])) {
            throw new PolicyException('redirect_forbidden');
        }
        if (isset($settings['protocols']) && !$this->webProtocols($settings['protocols'])) {
            throw new PolicyException('redirect_forbidden');
        }
    }

    /** Names only: values never enter diagnostics.
     * @return list<string>
     */
    public static function processProxyNames(): array
    {
        $names = [];
        foreach (self::processEnvironment() as $name => $value) {
            if (in_array(strtolower((string) $name), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true) && $value !== '') {
                $names[] = (string) $name;
            }
        }

        return $names;
    }

    /** @param array<string,mixed> $options */
    private function assertNoProxy(array $options): void
    {
        if (self::processProxyNames() !== []) {
            throw new PolicyException('proxy_unsupported');
        }
        if (array_key_exists('proxy', $options) && !in_array($options['proxy'], ['', null, []], true)) {
            throw new PolicyException('proxy_unsupported');
        }
    }

    private function nonnegativeNumber(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value) && $value >= 0;
    }

    /** @param array<string,mixed> $options
     * @return array<int,mixed>
     */
    private function managedAuthentication(array $options): array
    {
        $auth = $options['auth'] ?? null;
        if ($auth !== null && $auth !== false && (!is_array($auth) || !array_is_list($auth) || count($auth) < 2 || count($auth) > 3 || !is_string($auth[0]) || !is_string($auth[1]) || isset($auth[2]) && !in_array($auth[2], ['basic', 'digest', 'ntlm'], true))) {
            throw new PolicyException('option_forbidden');
        }
        if (!array_key_exists('curl', $options)) {
            return [];
        }
        if (RuntimeSupport::major() !== 7 || !is_array($auth) || !isset($auth[2]) || !in_array($auth[2], ['digest', 'ntlm'], true)) {
            throw new PolicyException('option_forbidden');
        }
        $username = $auth[0] ?? null;
        $password = $auth[1] ?? null;
        if (!is_string($username) || !is_string($password)) {
            throw new PolicyException('option_forbidden');
        }
        $expected = [
            CURLOPT_HTTPAUTH => $auth[2] === 'digest' ? CURLAUTH_DIGEST : CURLAUTH_NTLM,
            CURLOPT_USERPWD  => $username . ':' . $password,
        ];
        $raw = $options['curl'];
        if (!is_array($raw) || count($raw) !== 2 || ($raw[CURLOPT_HTTPAUTH] ?? null) !== $expected[CURLOPT_HTTPAUTH] || ($raw[CURLOPT_USERPWD] ?? null) !== $expected[CURLOPT_USERPWD]) {
            throw new PolicyException('option_forbidden');
        }

        // Rebuild only source-proven Guzzle7 generated authentication controls.
        return $expected;
    }

    private function webProtocols(mixed $protocols): bool
    {
        if (!is_array($protocols) || !array_is_list($protocols) || $protocols === []) {
            return false;
        }
        foreach ($protocols as $protocol) {
            if (!is_string($protocol) || !in_array($protocol, ['http', 'https'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * PHP normalizes numeric environment names to integer array keys.
     *
     * @return array<array-key, string>
     */
    private static function processEnvironment(): array
    {
        return getenv(null, true);
    }

    /**
     * @param array<array-key,mixed> $options
     *
     * @phpstan-assert array<string,mixed> $options
     */
    private function assertKnownOptions(array $options): void
    {
        foreach (array_keys($options) as $key) {
            if (!is_string($key) || !in_array($key, self::KNOWN, true)) {
                throw new PolicyException('option_forbidden');
            }
        }
    }
}
