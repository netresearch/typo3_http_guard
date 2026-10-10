<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Netresearch\HttpGuard\OutboundPolicyExceptionInterface;
use Netresearch\NrHttpGuard\Client\EndpointClientFactory;
use Netresearch\NrHttpGuard\Client\PublicFetchClient;
use Netresearch\NrHttpGuard\Http\MiddlewareRegistry;
use Netresearch\NrHttpGuard\Tests\Fixtures\ProbeState;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;

$fixture = realpath($argv[1]);
$classic = ($argv[2] ?? null) === 'classic';
putenv('TYPO3_PATH_ROOT=' . ($classic ? $fixture : $fixture . '/public'));
putenv('TYPO3_PATH_APP=' . $fixture);
$loader = require $fixture . '/vendor/autoload.php';
$checks = [];
function record(bool $condition, string $name, array $details = []): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException('ASSERTION_FAILED: ' . $name);
    }
    $checks[] = ['check' => $name, 'status' => 'PASS', 'details' => $details];
}
function wire(): array
{
    $values = [];
    foreach (['public' => '203.0.114.102', 'private' => '10.23.5.12'] as $name => $ip) {
        $curl = curl_init('http://' . $ip . ':8080/counters');
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_PROXY => '', CURLOPT_TIMEOUT => 3]);
        $body = curl_exec($curl);
        if (!is_string($body)) {
            throw new RuntimeException('wire_counter_unavailable');
        }
        $values[$name] = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        unset($curl);
    }

    return $values;
}
function noNewContact(array $before): bool
{
    $after = wire();
    foreach (['public', 'private'] as $target) {
        if ($after[$target]['requests'] !== $before[$target]['requests'] || $after[$target]['targetTcpAccepts'] !== $before[$target]['targetTcpAccepts']) {
            return false;
        }
    }

    return true;
}
function rejected(callable $operation, string $reason, string $check): void
{
    $before = wire();
    $caught = false;
    try {
        $operation();
    } catch (Throwable $exception) {
        $caught = $exception instanceof OutboundPolicyExceptionInterface ? $exception->reasonCode() === $reason : str_contains($exception->getMessage(), $reason);
    }
    record(
        $caught && noNewContact($before),
        $check,
        ['reasonCode' => $reason, 'zeroNewTcpAcceptsAndHttpRequests' => noNewContact($before)],
    );
}
try {
    SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
    $container      = Bootstrap::init($loader);
    $requestFactory = $container->get(RequestFactory::class);
    record($requestFactory instanceof RequestFactory, 'actual_production_bootstrap_and_core_request_factory');
    record(
        array_keys($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']) === [MiddlewareRegistry::BOUNDARY, 'project/A', 'project/B', MiddlewareRegistry::TERMINAL],
        'first_boundary_last_terminal',
    );
    $replacement = Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility::replacementClass();
    record(
        $requestFactory::class === $replacement && TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(RequestFactory::class) === $requestFactory && $container->get(Psr\Http\Message\RequestFactoryInterface::class) === $requestFactory,
        'real_core_di_makeinstance_psr17_same_validated_raw_factory',
        ['replacement' => $replacement, 'parentReadonly' => (new ReflectionClass(RequestFactory::class))->isReadOnly()],
    );
    rejected(
        fn () => $requestFactory->request('http://guard.test:8080/a#', 'GET', ['allow_redirects' => false], 'request'),
        'invalid_target',
        'core_raw_empty_fragment_denied_before_uri_construction',
    );
    rejected(
        fn () => $requestFactory->request('http://guard.test:8080/a\b', 'GET', ['allow_redirects' => false], 'request'),
        'invalid_target',
        'core_raw_backslash_denied_before_uri_construction',
    );
    record(
        (string) $requestFactory->createRequest('GET', '/relative?value=unchanged')->getUri() === '/relative?value=unchanged',
        'psr17_relative_request_construction_preserved',
    );
    $savedMapping                                                        = $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][RequestFactory::class];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][RequestFactory::class] = ['className' => 'Synthetic\ConflictingFactory'];
    rejected(
        fn () => $requestFactory->request('http://guard.test:8080/a', 'GET', ['allow_redirects' => false], 'request'),
        'configuration_invalid',
        'late_raw_factory_mapping_conflict_denied_before_http',
    );
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][RequestFactory::class] = $savedMapping;
    $corpusPath                                                          = ($classic ? $fixture . '/typo3conf/ext/nr_http_guard' : dirname(__DIR__, 2)) . '/Resources/Private/HttpGuard/data/security-corpus/endpoint-cases.json';
    $corpus                                                              = json_decode(file_get_contents($corpusPath), true, flags: JSON_THROW_ON_ERROR);
    $cases                                                               = array_values(array_filter($corpus['cases'], static fn (array $case): bool => $case['id'] === 'EP-PRIVATE-UNBOUND'));
    if (count($cases) !== 1) {
        throw new RuntimeException('normative_corpus_case_missing');
    }
    $case         = $cases[0];
    $terminal     = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'][MiddlewareRegistry::TERMINAL];
    $transfers    = (new ReflectionProperty($terminal, 'transfers'))->getValue($terminal);
    $driver       = (new ReflectionProperty($transfers, 'driver'))->getValue($transfers);
    $beforeNative = $driver->counters()['nativeConstructed'];
    rejected(
        fn () => $requestFactory->request(
            $case['input']['origin'] . '/unbound-corpus',
            $case['input']['method'],
            ['allow_redirects' => false],
            'request',
        ),
        $case['expected']['reason'],
        $case['id'] . '-shared_corpus_denied',
    );
    record(
        $driver->counters()['nativeConstructed'] === $beforeNative && $case['input']['registry_issued_client_binding'] === false && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']['endpoints']['corpus-unbound']),
        'shared_normative_case_consumed_with_configured_but_unbound_endpoint',
        [
            'caseId'                  => $case['id'],
            'corpusSha256'            => hash_file('sha256', $corpusPath),
            'origin'                  => $case['input']['origin'],
            'method'                  => $case['input']['method'],
            'address'                 => $case['input']['address'],
            'nativeConstructionDelta' => $driver->counters()['nativeConstructed'] - $beforeNative,
        ],
    );
    ProbeState::$trace = [];
    $before            = wire();
    $response          = $requestFactory->request(
        'http://guard.test:8080/a',
        'GET',
        [
            'allow_redirects' => false,
            'on_headers'      => static function (): void {
                ProbeState::$trace[] = 'user.on_headers';
            },
            'on_stats' => static function (): void {
                ProbeState::$trace[] = 'user.on_stats';
            },
        ],
        'request',
    );
    $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    record(
        $body['server'] === 'U' && $body['headers']['Host'] === 'guard.test:8080',
        'global_core_request_pinned_without_changing_host',
    );
    record(
        ProbeState::$trace === ['A.request', 'B.request', 'user.on_headers', 'user.on_stats', 'B.response', 'A.response'],
        'existing_middleware_and_callbacks_preserved',
        ['trace' => ProbeState::$trace],
    );
    rejected(
        fn () => $requestFactory->request('http://erp.test:8080/a', 'GET', ['allow_redirects' => false], 'request'),
        'address_forbidden',
        'configured_endpoint_does_not_grant_global_core_requests',
    );
    record(
        ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts'][0] ?? null) === 'erp.test',
        'flat_legacy_vault_entry_present_without_new_grant',
    );
    rejected(
        fn () => $requestFactory->request(
            'http://erp.test:8080/legacy-flat-must-not-grant',
            'GET',
            ['allow_redirects' => false],
            'request',
        ),
        'address_forbidden',
        'flat_legacy_vault_allowlist_does_not_grant_public_core_private_access',
    );
    ProbeState::$trace = [];
    rejected(
        fn () => $requestFactory->request('http://denied.test:8080/a', 'GET', ['allow_redirects' => false], 'request'),
        'is not allowed',
        'core_context_allowlist_is_cumulative',
    );
    record(ProbeState::$trace === [], 'core_allowlist_denial_precedes_custom_middleware');
    ProbeState::$rewrite = true;
    rejected(
        fn () => $requestFactory->request('http://guard.test:8080/a', 'GET', ['allow_redirects' => false], 'request'),
        'authority_mismatch',
        'custom_origin_rewrite_denied_before_tcp',
    );
    ProbeState::$rewrite                                   = false;
    $registry                                              = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'];
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['late'] = new Netresearch\NrHttpGuard\Tests\Fixtures\RecordingMiddleware('LATE');
    rejected(
        fn () => $requestFactory->request('http://guard.test:8080/a'),
        'configuration_invalid',
        'late_middleware_after_terminal_denied_before_tcp',
    );
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']              = $registry;
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['duplicate'] = $registry[MiddlewareRegistry::BOUNDARY];
    rejected(
        fn () => $requestFactory->request('http://guard.test:8080/a'),
        'configuration_invalid',
        'duplicate_guard_denied_before_tcp',
    );
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $registry;
    foreach ([
        ['stream' => true],
        ['delay' => 1],
        ['unknown_transport_option' => true],
        ['curl' => [CURLOPT_CONNECT_TO => ['guard.test:8080:10.23.5.12:8080']]],
    ] as $options) {
        rejected(
            fn () => $requestFactory->request('http://guard.test:8080/a', 'GET', $options),
            'option_forbidden',
            'options_denied_before_tcp_' . array_key_first($options),
        );
    }
    $endpointFactory   = $container->get(EndpointClientFactory::class);
    $endpoint          = $endpointFactory->forEndpoint('erp-orders');
    ProbeState::$trace = [];
    $response          = $endpoint->sendRequest(new Request('GET', 'http://erp.test:8080/a'));
    $body              = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    record(
        $body['server'] === 'P' && ProbeState::$trace === ['A.request', 'B.request', 'B.response', 'A.response'],
        'bound_endpoint_private_wire_and_existing_core_stack',
    );
    rejected(
        fn () => $endpoint->sendRequest(new Request('DELETE', 'http://erp.test:8080/a')),
        'endpoint_mismatch',
        'bound_endpoint_method_denied_before_tcp',
    );
    rejected(
        fn () => $endpoint->sendRequest(new Request('GET', 'http://guard.test:8080/a')),
        'endpoint_mismatch',
        'bound_endpoint_origin_denied_before_tcp',
    );
    $savedAllowlist                                                       = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['nr_http_guard'];
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['nr_http_guard'] = ['guard.test'];
    $restrictedEndpoint                                                   = $endpointFactory->forEndpoint('erp-orders');
    rejected(
        fn () => $restrictedEndpoint->sendRequest(new Request('GET', 'http://erp.test:8080/a')),
        'is not allowed',
        'endpoint_factory_preserves_fixed_core_context',
    );
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['nr_http_guard'] = $savedAllowlist;
    $before                                                               = wire();
    $response                                                             = $endpoint->sendRequest(new Request('GET', 'http://erp.test:8080/redirect'));
    record(
        $response->getStatusCode() === 307 && wire()['private']['requests'] === $before['private']['requests'] + 1,
        'psr18_send_request_does_not_follow_redirect',
    );
    $httpBefore                                                     = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['auth']                     = ['fake-default-user', 'fake-default-password'];
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['headers']['Authorization'] = 'Bearer fake-global-secret';
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['cookies']                  = GuzzleHttp\Cookie\CookieJar::fromArray(['fake-cookie' => 'fake-cookie-secret'], 'guard.test');
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['cert']                     = '/nonexistent/fake-client-certificate.pem';
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_key']                  = '/nonexistent/fake-client-key.pem';
    $publicFetch                                                    = $container->get(PublicFetchClient::class);
    ProbeState::$trace                                              = [];
    $response                                                       = $publicFetch->fetch(new Uri('http://guard.test:8080/a'));
    $body                                                           = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    record(
        $body['headers']['Authorization'] === null && $body['headers']['Cookie'] === null && $body['headers']['User-Agent'] === 'HTTP-Guard-PublicFetch/0.1',
        'public_fetch_strips_global_auth_cookies_mtls_and_headers',
    );
    record(
        ProbeState::$trace === ['A.request', 'B.request', 'B.response', 'A.response'],
        'public_fetch_keeps_existing_custom_stack',
    );
    rejected(
        fn () => $publicFetch->fetch(new Uri('http://erp.test:8080/a')),
        'address_forbidden',
        'public_fetch_cannot_choose_endpoint_grant',
    );
    rejected(
        fn () => $publicFetch->fetch(new Uri('http://denied.test:8080/a')),
        'is not allowed',
        'public_fetch_keeps_fixed_core_context',
    );
    $before   = wire();
    $response = $publicFetch->fetch(new Uri('http://guard.test:8080/cross-redirect'));
    record(
        $response->getStatusCode() === 200 && wire()['public']['requests'] === $before['public']['requests'] + 2,
        'explicit_public_fetch_can_follow_scrubbed_cross_origin',
    );
    $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = $httpBefore;
    ProbeState::$lateRedirect           = true;
    $before                             = wire();
    $caught                             = false;
    try {
        $requestFactory->request('http://guard.test:8080/a', 'GET', [], 'request');
    } catch (Throwable $exception) {
        $caught = $exception instanceof OutboundPolicyExceptionInterface && $exception->reasonCode() === 'redirect_forbidden';
    }
    $after = wire();
    record(
        $caught && $after['public']['requests'] === $before['public']['requests'] + 1 && $after['private']['targetTcpAccepts'] === $before['private']['targetTcpAccepts'],
        'final_response_middleware_rewrite_denied_before_private_follow',
    );
    ProbeState::$lateRedirect = false;
    $promise                  = $container
        ->get(GuzzleClientFactory::class)
        ->getClient('request')
        ->requestAsync('GET', 'http://guard.test:8080/a', ['allow_redirects' => false]);
    record($promise->wait()->getStatusCode() === 200, 'actual_core_async_promise_uses_controlled_transport');
    echo json_encode(
        [
            'status'   => 'PASS',
            'fixture'  => $fixture,
            'php'      => PHP_VERSION,
            'checks'   => $checks,
            'versions' => [
                'core'   => (new TYPO3\CMS\Core\Information\Typo3Version())->getVersion(),
                'guzzle' => Composer\InstalledVersions::getPrettyVersion('guzzlehttp/guzzle'),
            ],
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    ) . "\n";
} catch (Throwable $exception) {
    echo json_encode(
        [
            'status'     => 'FAIL',
            'fixture'    => $fixture,
            'class'      => $exception::class,
            'reasonCode' => $exception instanceof OutboundPolicyExceptionInterface ? $exception->reasonCode() : null,
            'error'      => $exception->getMessage(),
            'trace'      => $exception->getTraceAsString(),
            'checks'     => $checks,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    ) . "\n";
    exit(1);
}
