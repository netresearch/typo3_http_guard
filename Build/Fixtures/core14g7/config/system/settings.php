<?php

declare (strict_types=1);
require_once dirname(__DIR__, 5) . '/Tests/Fixtures/RecordingMiddleware.php';
$corpus = json_decode(
    file_get_contents(
        dirname(__DIR__, 5) . '/Resources/Private/HttpGuard/data/security-corpus/endpoint-cases.json'
    ),
    true,
    flags: JSON_THROW_ON_ERROR
);
$unboundCases = array_values(
    array_filter(
        $corpus['cases'],
        static fn(array $case): bool => $case['id'] === 'EP-PRIVATE-UNBOUND'
    )
);
if (count($unboundCases) !== 1) {
    throw new RuntimeException('shared_security_corpus_case_missing');
}
$unbound = $unboundCases[0]['input'];
$unboundHost = parse_url($unbound['origin'], PHP_URL_HOST);
return [
    'SYS' => [
        'encryptionKey' => 'local-disposable-http-guard-fixture',
        'displayErrors' => 1,
        'exceptionalErrors' => 0,
        'caching' => [
            'cacheConfigurations' => [
                'core' => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
                ],
            ],
        ],
    ],
    'HTTP' => [
        'timeout' => 3,
        'proxy' => '',
        'verify' => true,
        'allowed_hosts' => [
            0 => 'erp.test',
            'request' => ['guard.test', 'erp.test', $unboundHost],
            'nr_http_guard' => ['guard.test', 'guard2.test', 'erp.test'],
        ],
        'handler' => [
            'project/A' => new \Netresearch\NrHttpGuard\Tests\Fixtures\RecordingMiddleware('A'),
            'project/B' => new \Netresearch\NrHttpGuard\Tests\Fixtures\RecordingMiddleware('B'),
        ],
    ],
    'EXTCONF' => [
        'nr_http_guard' => [
            'schemaVersion' => 1,
            'resolver' => [
                'staticHosts' => [
                    'guard.test' => ['203.0.114.102'],
                    'guard2.test' => ['203.0.114.102'],
                    'denied.test' => ['203.0.114.102'],
                    'erp.test' => ['10.23.5.12'],
                    $unboundHost => [$unbound['address']],
                ],
            ],
            'endpoints' => [
                'corpus-unbound' => [
                    'origin' => $unbound['origin'],
                    'allowedCidrs' => $unbound['allowed_cidrs'],
                    'methods' => [$unbound['method']],
                    'allowLoopback' => $unbound['allow_loopback'],
                    'purpose' => 'Shared normative security corpus',
                    'owner' => 'Test maintainers',
                ],
                'erp-orders' => [
                    'origin' => 'http://erp.test:8080',
                    'allowedCidrs' => ['10.23.5.12/32'],
                    'methods' => ['GET', 'POST'],
                    'redirects' => 'none',
                    'allowLoopback' => false,
                    'purpose' => 'Synthetic local integration fixture',
                    'owner' => 'HTTP Guard test maintainers',
                ],
            ],
        ],
    ],
];
