<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);
require dirname(__DIR__, 2) . '/bootstrap.php';
if (($_SERVER['REQUEST_URI'] ?? '') === '/ready') {
    header('Content-Type: text/plain');
    echo 'ready';

    return;
}
header('Content-Type: application/json');
try {
    $headers        = getallheaders();
    $incomingHeader = null;
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'proxy') {
            $incomingHeader = $value;
        }
    }
    $headerPresent = $incomingHeader === 'http://10.23.4.12:8080';
    $proxyNames    = Netresearch\HttpGuard\Transport\OptionSanitizer::processProxyNames();
    $config        = Netresearch\HttpGuard\GuardConfig::fromArray(['resolver' => ['staticHosts' => ['guard.test' => ['203.0.115.100']]]]);
    $clock         = new Netresearch\HttpGuard\SystemClock();
    $registry      = new Netresearch\HttpGuard\PolicyRegistry($config, $clock);
    $query         = new class implements Netresearch\HttpGuard\DnsQueryInterface {
        public function query(string $absoluteFqdn, int $qtype): Netresearch\HttpGuard\DnsAnswer
        {
            throw new LogicException('Static fixture must not query DNS');
        }
    };
    $resolver = new Netresearch\HttpGuard\StaticThenDnsResolver($config, $query, $clock);
    $engine   = new Netresearch\HttpGuard\PolicyEngine(
        new Netresearch\HttpGuard\TargetNormalizer(),
        new Netresearch\HttpGuard\AddressClassifier(),
        $resolver,
        $registry,
        $clock,
        new Netresearch\HttpGuard\NullDecisionReporter(),
    );
    $factory  = new Netresearch\HttpGuard\Client\GuardedClientFactory($engine, $config, $registry);
    $binding  = $factory->createTransport();
    $response = $binding->client->request('GET', 'http://guard.test:8090/echo');
    $echo     = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    echo json_encode(
        [
            'status'                     => 'PASS',
            'incomingProxyHeaderPresent' => $headerPresent,
            'serverHttpProxyPresent'     => array_key_exists('HTTP_PROXY', $_SERVER),
            'sapiHttpProxyValue'         => $_SERVER['HTTP_PROXY'] ?? null,
            'processProxyNames'          => $proxyNames,
            'transport'                  => $binding->driver->counters(),
            'targetLabel'                => $echo['label'] ?? null,
            'targetHost'                 => $echo['host'] ?? null,
            'sapi'                       => PHP_SAPI,
        ],
        JSON_THROW_ON_ERROR,
    );
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['status' => 'FAIL', 'errorClass' => get_class($error)], JSON_THROW_ON_ERROR);
}
