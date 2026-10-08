<?php

$loader = require __DIR__ . '/transport-only/vendor/autoload.php';
require __DIR__ . '/extension/Classes/Runtime.php';
$request = new GuzzleHttp\Psr7\Request('GET', 'http://guard.test:8080/a');
$options = [
    'proxy' => '',
    'timeout' => 2,
    'curl' => [
        CURLOPT_RESOLVE => ['guard.test:8080:203.0.114.100'],
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => true,
    ],
];
try {
    $response = (new HttpGuardProbe\Lease())
        ->start($request, $options, '203.0.114.100', 'guard.test:8080:203.0.114.100')
        ->wait();
    echo $response->getStatusCode() . ' ' . $response->getBody() . "\n";
    gc_collect_cycles();
    $live = count(
        array_filter(
            HttpGuardProbe\State::$weakHandlers,
            fn($ref) => $ref->get() !== null
        )
    );
    echo json_encode(
        [
            'attempts' => HttpGuardProbe\State::$attempts,
            'released' => HttpGuardProbe\State::$released,
            'live' => $live,
        ]
    ) . "\n";
} catch (Throwable $e) {
    echo $e::class . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
