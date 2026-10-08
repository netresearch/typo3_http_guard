<?php

declare (strict_types=1);
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\RequestFactory;
use Netresearch\NrHttpGuard\Tests\Fixtures\ProbeState;
$fixture = realpath($argv[1]);
$mode = $argv[2];
if (!in_array($mode, ['observe', 'disabled'], true)) {
    throw new RuntimeException('mode_fixture_invalid');
}
putenv('TYPO3_PATH_ROOT=' . $fixture . '/public');
putenv('TYPO3_PATH_APP=' . $fixture);
putenv('HTTP_GUARD_BOOT_TEST=' . $mode);
$loader = require $fixture . '/vendor/autoload.php';
try {
    SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
    $container = Bootstrap::init($loader);
    ProbeState::$trace = [];
    $response = $container
        ->get(RequestFactory::class)
        ->request(
            'http://erp.test:8080/a',
            'GET',
            [
                'allow_redirects' => false,
                'verify' => false,
                'curl' => [CURLOPT_RESOLVE => ['erp.test:8080:10.23.5.12']],
                'legacy_opaque_option' => true,
                'headers' => ['X-Mode-Contract' => 'fake-mode-contract'],
                'on_headers' => static function (): void {
                    ProbeState::$trace[] = 'headers';
                },
                'on_stats' => static function (): void {
                    ProbeState::$trace[] = 'stats';
                },
            ],
            'request'
        );
    $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    if ($body['server'] !== 'P' || ProbeState::$trace !== ['A.request', 'B.request', 'headers', 'stats', 'B.response', 'A.response']) {
        throw new RuntimeException('mode_transparency_failed');
    }
    $trace = ProbeState::$trace;
    foreach (['/raw-mode-a#' => '/raw-mode-a', '/raw-mode-a\b' => '/raw-mode-a%5Cb'] as $rawPath => $expectedPath) {
        $rawResponse = $container
            ->get(RequestFactory::class)
            ->request(
                'http://erp.test:8080' . $rawPath,
                'GET',
                [
                    'allow_redirects' => false,
                    'curl' => [CURLOPT_RESOLVE => ['erp.test:8080:10.23.5.12']],
                ],
                'request'
            );
        $rawBody = json_decode(
            (string) $rawResponse->getBody(),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        if ($rawBody['server'] !== 'P' || $rawBody['path'] !== $expectedPath) {
            throw new RuntimeException(
                'raw_factory_unprotected_mode_changed_original_core_uri_behavior'
            );
        }
    }
    echo json_encode(
        [
            'status' => 'PASS',
            'mode' => $mode,
            'protected' => false,
            'actualCoreLeafReachedPrivateTarget' => true,
            'callerRawOptionsAndCallbacksPreserved' => true,
            'trace' => $trace,
            'rawStringBehaviorPreserved' => true,
        ],
        JSON_PRETTY_PRINT
    ) . "\n";
} catch (Throwable $exception) {
    echo json_encode(
        [
            'status' => 'FAIL',
            'mode' => $mode,
            'class' => $exception::class,
            'error' => $exception->getMessage(),
        ],
        JSON_PRETTY_PRINT
    ) . "\n";
    exit(1);
}
