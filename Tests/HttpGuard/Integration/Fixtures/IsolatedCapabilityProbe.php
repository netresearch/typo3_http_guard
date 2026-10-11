<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace GuzzleHttp\Handler {
    function curl_setopt(mixed $handle, int $option, mixed $value): bool
    {
        if ($option === \CURLOPT_RESOLVE && ($GLOBALS['http_guard_force_resolve_failure'] ?? false)) {
            ++$GLOBALS['http_guard_forced_resolve_failures'];

            return false;
        }

        return \curl_setopt($handle, $option, $value);
    }
}

namespace {
    require dirname(__DIR__, 2) . '/bootstrap.php';
    $mode        = $argv[1] ?? '';
    $unsupported = in_array($mode, ['dependency_mismatch', 'missing_curl_multi', 'missing_curl'], true);
    if (!$unsupported && $mode !== 'curl_option_failure') {
        throw new RuntimeException('Unknown isolated probe mode');
    }
    foreach (getenv(null, true) as $key => $value) {
        if (in_array(strtolower((string) $key), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
            putenv((string) $key);
        }
    }
    $counters = static function (): array {
        $result = [];
        foreach (['public-a' => '203.0.115.100', 'public-b' => '203.0.115.101', 'private' => '10.23.4.12'] as $label => $ip) {
            $handle = \curl_init('http://' . $ip . ':8091/stats');
            \curl_setopt_array($handle, [CURLOPT_PROXY => '', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2]);
            $body = \curl_exec($handle);
            unset($handle);
            if (!is_string($body)) {
                throw new RuntimeException('Owned synthetic counter unavailable');
            }
            $result[$label] = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        return $result;
    };
    $before = extension_loaded('curl') ? $counters() : [];
    if ($mode === 'dependency_mismatch') {
        $data                                                    = Composer\InstalledVersions::getAllRawData()[0];
        $data['versions']['guzzlehttp/guzzle']['pretty_version'] = '9.99.99';
        $data['versions']['guzzlehttp/guzzle']['version']        = '9.99.99.0';
        foreach (Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->unregister();
            spl_autoload_register([$loader, 'loadClass'], true, true);
        }
        Composer\InstalledVersions::reload($data);
    } elseif ($mode === 'curl_option_failure') {
        $GLOBALS['http_guard_force_resolve_failure']   = true;
        $GLOBALS['http_guard_forced_resolve_failures'] = 0;
    }
    if ($mode === 'missing_curl' && extension_loaded('curl')) {
        throw new LogicException('Missing-curl fixture must run on a PHP binary genuinely without curl');
    }
    if ($mode === 'missing_curl_multi' && function_exists('curl_multi_exec')) {
        throw new LogicException('Missing-multi fixture must disable the real function');
    }
    $config   = Netresearch\HttpGuard\GuardConfig::fromArray([]);
    $clock    = new Netresearch\HttpGuard\SystemClock();
    $registry = new Netresearch\HttpGuard\PolicyRegistry($config, $clock);
    $resolver = new class implements Netresearch\HttpGuard\ResolverInterface {
        public function resolve(string $host): Netresearch\HttpGuard\Resolution
        {
            return new Netresearch\HttpGuard\Resolution(['203.0.115.100'], 'fixture', 0, 'fixture');
        }
    };
    $engine = new Netresearch\HttpGuard\PolicyEngine(
        new Netresearch\HttpGuard\TargetNormalizer(),
        new Netresearch\HttpGuard\AddressClassifier(),
        $resolver,
        $registry,
        $clock,
        new Netresearch\HttpGuard\NullDecisionReporter(),
    );
    $factory = new Netresearch\HttpGuard\Client\GuardedClientFactory($engine, $config, $registry);
    $binding = $factory->createTransport();
    $caught  = null;
    try {
        $binding->client->request('GET', 'http://guard.test:8090/echo');
    } catch (Throwable $error) {
        $caught = $error;
    }
    $after    = extension_loaded('curl') ? $counters() : [];
    $wireZero = true;
    foreach ($before as $label => $counts) {
        $wireZero = $wireZero && $counts['tcp'] === $after[$label]['tcp'] && $counts['requests'] === $after[$label]['requests'];
    }
    $transport     = $binding->driver->counters();
    $correctError  = $unsupported ? $caught instanceof Netresearch\HttpGuard\PolicyException && $caught->reasonCode() === 'transport_unsupported' : $caught instanceof Throwable && ($GLOBALS['http_guard_forced_resolve_failures'] ?? 0) > 0;
    $correctNative = $transport['nativeConstructed'] === ($unsupported ? 0 : 1);
    $good          = $correctError && $correctNative && $wireZero && $transport['active'] === 0;
    echo json_encode(
        [
            'status'                   => $good ? 'PASS' : 'FAIL',
            'mode'                     => $mode,
            'errorClass'               => $caught ? get_class($caught) : null,
            'errorFile'                => $caught ? $caught->getFile() : null,
            'errorLine'                => $caught ? $caught->getLine() : null,
            'reasonCode'               => $caught instanceof Netresearch\HttpGuard\PolicyException ? $caught->reasonCode() : null,
            'forcedResolveSetFailures' => $GLOBALS['http_guard_forced_resolve_failures'] ?? 0,
            'transport'                => $transport,
            'zeroNewTcpAndHttp'        => $wireZero,
            'counterSource'            => extension_loaded('curl') ? 'child_and_parent' : 'parent_only',
            'curlLoaded'               => extension_loaded('curl'),
            'curlMultiAvailable'       => function_exists('curl_multi_exec'),
            'guzzleMetadataVersion'    => Composer\InstalledVersions::getPrettyVersion('guzzlehttp/guzzle'),
            'phpVersion'               => PHP_VERSION,
            'before'                   => $before,
            'after'                    => $after,
        ],
        JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
    ) . "\n";
    exit($good ? 0 : 1);
}
