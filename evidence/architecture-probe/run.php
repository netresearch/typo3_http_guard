<?php

declare (strict_types=1);

use HttpGuardProbe\State;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

$fixture = realpath($argv[1]);
$bootCase = $argv[2] ?? 'normal';
putenv('TYPO3_PATH_ROOT=' . $fixture . '/public');
putenv('TYPO3_PATH_APP=' . $fixture);
putenv('PROBE_BOOT_CASE=' . $bootCase);
$loader = require $fixture . '/vendor/autoload.php';
$checks = [];

function check(bool $condition, string $name, array $details = []): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException(
            'ASSERTION_FAILED: ' . $name . ' ' . json_encode($details)
        );
    }
    $checks[] = ['check' => $name, 'status' => 'PASS', 'details' => $details];
}

function counters(): array
{
    $result = [];
    foreach (['A' => '203.0.114.100', 'B' => '203.0.114.101'] as $name => $ip) {
        $curl = curl_init('http://' . $ip . ':8080/counters');
        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PROXY => '',
                CURLOPT_TIMEOUT => 3,
            ]
        );
        $body = curl_exec($curl);
        if (!is_string($body)) {
            throw new RuntimeException('wire_counter_unavailable');
        }
        $result[$name] = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    }
    return $result;
}

function unchanged(array $before): bool
{
    $after = counters();
    return $after['A']['requests'] === $before['A']['requests'] && $after['B']['requests'] === $before['B']['requests'];
}

function deny(callable $operation, string $reason, string $name): void
{
    $before = counters();
    $seen = false;
    try {
        $operation();
    } catch (Throwable $e) {
        $seen = str_contains($e->getMessage(), $reason);
    }
    check(
        $seen && unchanged($before),
        $name,
        [
            'requiredReason' => $reason,
            'zeroNewWireRequests' => unchanged($before),
        ]
    );
}

function drive(): void
{
    $until = microtime(true) + 4;
    while (State::$leases !== [] && microtime(true) < $until) {
        foreach (array_values(State::$leases) as $lease) {
            $lease->tick();
        }
        GuzzleHttp\Promise\Utils::queue()->run();
        usleep(1000);
    }
    check(State::$leases === [], 'async_finished_before_deadline');
}

function released(string $name): void
{
    gc_collect_cycles();
    $states = array_map(
        static function (WeakReference $ref): array {
            $handler = $ref->get();
            if (!$handler) {
                return ['destroyed' => true];
            }
            $reflection = new ReflectionObject($handler);
            $values = ['destroyed' => false];
            foreach (['closed', 'handles', '_mh', 'multiHandle'] as $property) {
                if ($reflection->hasProperty($property)) {
                    $slot = $reflection->getProperty($property);
                    $value = $slot->isInitialized($handler) ? $slot->getValue($handler) : null;
                    $values[$property] = is_array($value) ? count($value) : (is_object($value) ? $value::class : $value);
                }
            }
            $factory = $reflection->getProperty('factory')->getValue($handler);
            $factoryReflection = new ReflectionObject($factory);
            $values['factoryIdleHandles'] = count(
                $factoryReflection->getProperty('handles')->getValue($factory)
            );
            return $values;
        },
        State::$weakHandlers
    );
    $live = count(
        array_filter(
            $states,
            static fn(array $state): bool => !$state['destroyed']
        )
    );
    $open = count(
        array_filter(
            $states,
            static function (array $state): bool {
                if ($state['destroyed']) {
                    return false;
                }
                if (($state['handles'] ?? -1) !== 0 || ($state['factoryIdleHandles'] ?? -1) !== 0) {
                    return true;
                }
                if (array_key_exists('closed', $state)) {
                    return $state['closed'] !== true || ($state['multiHandle'] ?? null) !== null;
                }
                return ($state['_mh'] ?? null) !== null;
            }
        )
    );
    check(
        State::$leases === [] && State::$attempts === State::$released && $open === 0,
        $name,
        [
            'attempts' => State::$attempts,
            'released' => State::$released,
            'liveHandlerReferences' => $live,
            'openNativeHandlers' => $open,
            'handlerStates' => $states,
        ]
    );
}

try {
    SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
    $beforeBoot = counters();
    try {
        $container = Bootstrap::init($loader);
    } catch (Throwable $e) {
        if (in_array($bootCase, ['object', 'duplicate'], true) && str_contains($e->getMessage(), 'configuration_invalid')) {
            check(
                unchanged($beforeBoot),
                'invalid_registry_rejected_in_real_bootstrap_before_wire',
                ['case' => $bootCase]
            );
            echo json_encode(
                [
                    'status' => 'PASS',
                    'fixture' => $fixture,
                    'case' => $bootCase,
                    'checks' => $checks,
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) . "\n";
            exit(0);
        }
        throw $e;
    }
    check($bootCase === 'normal', 'invalid_boot_case_must_have_failed');
    check(
        State::$registered === 1,
        'real_bootstrap_registered_listener_once',
        ['bootstrap' => Bootstrap::class, 'registered' => State::$registered]
    );
    $requestFactory = $container->get(RequestFactory::class);
    check(
        $requestFactory instanceof RequestFactory,
        'actual_core_request_factory_service'
    );
    State::assertRegistry();
    check(
        array_keys($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']) === [
            'nr/http-guard-boundary',
            'project/A',
            'project/B',
            'nr/http-guard-terminal',
        ],
        'boundary_first_terminal_last'
    );

    State::$trace = [];
    $before = counters();
    $response = $requestFactory->request(
        'http://guard.test:8080/a',
        'GET',
        [
            'allow_redirects' => false,
            'on_headers' => static function (
                \Psr\Http\Message\ResponseInterface $response,
                ?\Psr\Http\Message\RequestInterface $request = null
            ): void {
                State::$trace[] = 'user.on_headers';
                $g8 = str_starts_with(
                    Composer\InstalledVersions::getPrettyVersion(
                        'guzzlehttp/guzzle'
                    ),
                    '8.'
                );
                check(
                    $response->getStatusCode() === 200 && func_num_args() === ($g8 ? 2 : 1) && (!$g8 || $request?->getUri()->getHost() === 'guard.test'),
                    'on_headers_actual_versioned_arguments_preserved',
                    [
                        'argumentCount' => func_num_args(),
                        'responseClass' => $response::class,
                        'requestClass' => $request ? $request::class : null,
                    ]
                );
            },
            'on_stats' => static function (\GuzzleHttp\TransferStats $stats): void {
                State::$trace[] = 'user.on_stats';
                check(
                    $stats->hasResponse() && $stats->getResponse()->getStatusCode() === 200 && $stats->getHandlerStat('primary_ip') === '203.0.114.100',
                    'on_stats_actual_transfer_context_preserved',
                    [
                        'class' => $stats::class,
                        'primaryIp' => $stats->getHandlerStat('primary_ip'),
                    ]
                );
            },
        ],
        'probe'
    );
    $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    check(
        $body['server'] === 'A' && counters()['A']['requests'] === $before['A']['requests'] + 1 && counters()['B']['requests'] === $before['B']['requests'],
        'pinned_actual_request_factory_wire_target'
    );
    $expected = [
        'boundary.request',
        'A.request',
        'B.request',
        'terminal.request',
        'wire.on_headers',
        'user.on_headers',
        'wire.on_stats',
        'user.on_stats',
        'terminal.response',
        'B.response',
        'A.response',
        'boundary.response',
    ];
    check(
        State::$trace === $expected,
        'request_response_and_callback_order_preserved',
        ['trace' => State::$trace]
    );
    released('sync_handler_teardown');

    State::$trace = [];
    deny(
        fn() => $requestFactory->request(
            'http://denied.test:8080/a',
            'GET',
            ['allow_redirects' => false],
            'probe'
        ),
        'is not allowed',
        'core_context_allowlist_still_blocks'
    );
    check(State::$trace === [], 'core_allowlist_runs_before_boundary');
    State::$rewrite = true;
    deny(
        fn() => $requestFactory->request(
            'http://guard.test:8080/a',
            'GET',
            ['allow_redirects' => false],
            'probe'
        ),
        'authority_mismatch',
        'middleware_origin_rewrite_denied_before_wire'
    );
    State::$rewrite = false;

    $registry = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'];
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['late'] = new HttpGuardProbe\ProjectMiddleware('LATE');
    deny(
        fn() => $requestFactory->request('http://guard.test:8080/a'),
        'configuration_invalid',
        'entry_after_terminal_rejected_before_wire'
    );
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $registry;
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['duplicate'] = State::$boundary;
    deny(
        fn() => $requestFactory->request('http://guard.test:8080/a'),
        'configuration_invalid',
        'duplicate_runtime_registration_rejected_before_wire'
    );
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = $registry;

    foreach ([
        ['stream' => true],
        [
            'curl' => [CURLOPT_CONNECT_TO => ['guard.test:8080:203.0.114.101:8080']],
        ],
        ['delay' => 10],
        ['unknown_option' => true],
    ] as $bad) {
        deny(
            fn() => $requestFactory->request('http://guard.test:8080/a', 'GET', $bad),
            'option_forbidden',
            'forbidden_options_before_wire_' . array_key_first($bad)
        );
    }

    State::$trace = [];
    $before = counters();
    $response = $requestFactory->request(
        'http://guard.test:8080/redirect',
        'GET',
        [],
        'probe'
    );
    $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    $after = counters();
    check(
        $body['server'] === 'B' && $after['A']['requests'] === $before['A']['requests'] + 1 && $after['B']['requests'] === $before['B']['requests'] + 1,
        'redirect_hops_reenter_guard_with_new_pin'
    );
    check(
        count(
            array_filter(
                State::$trace,
                fn($event) => $event === 'boundary.request'
            )
        ) === 2 && count(
            array_filter(
                State::$trace,
                fn($event) => $event === 'terminal.request'
            )
        ) === 2,
        'boundary_and_terminal_once_per_hop'
    );
    released('redirect_handler_teardown');

    State::$lateRedirect = true;
    $before = counters();
    $caught = false;
    try {
        $requestFactory->request(
            'http://guard.test:8080/late-redirect',
            'GET',
            [],
            'probe'
        );
    } catch (Throwable $e) {
        $caught = str_contains($e->getMessage(), 'redirect_forbidden');
    }
    $after = counters();
    check(
        $caught && $after['A']['requests'] === $before['A']['requests'] + 1 && $after['B']['requests'] === $before['B']['requests'],
        'final_response_rewrite_denied_before_follow'
    );
    State::$lateRedirect = false;
    unset($e);
    released('response_rewrite_handler_teardown');

    $client = $container->get(GuzzleClientFactory::class)->getClient('probe');
    $before = counters();
    $initialAttempt = State::$attempts;
    $promises = [];
    foreach (['slow-a', 'slow-b', 'slow-a', 'slow-b'] as $path) {
        $promises[] = $client->requestAsync(
            'GET',
            'http://guard.test:8080/' . $path,
            ['allow_redirects' => false]
        );
    }
    check(count(State::$leases) === 4, 'four_independent_leases_overlap');
    $plans = array_slice(State::$plans, $initialAttempt);
    check(
        count(array_unique(array_column($plans, 'handlerId'))) === 4,
        'distinct_curl_multi_handler_for_each_parallel_attempt',
        ['plans' => $plans]
    );
    drive();
    foreach ($promises as $i => $promise) {
        $body = json_decode(
            (string) $promise->wait()->getBody(),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        check(
            $body['server'] === ($i % 2 === 0 ? 'A' : 'B'),
            'parallel_response_target_' . $i
        );
    }
    $after = counters();
    check(
        $after['A']['requests'] === $before['A']['requests'] + 2 && $after['B']['requests'] === $before['B']['requests'] + 2 && ($after['A']['paths']['/slow-b'] ?? 0) === 0 && ($after['B']['paths']['/slow-a'] ?? 0) === 0,
        'parallel_same_origin_pins_isolated_by_separate_wire_counters',
        ['before' => $before, 'after' => $after]
    );
    released('parallel_handler_teardown');

    $promise = $client->requestAsync(
        'GET',
        'http://guard.test:8080/slow-a',
        ['allow_redirects' => false]
    );
    foreach (array_values(State::$leases) as $lease) {
        $lease->tick();
    }
    $promise->cancel();
    GuzzleHttp\Promise\Utils::queue()->run();
    released('cancelled_handler_teardown');

    $caught = false;
    try {
        $requestFactory->request(
            'http://guard.test:8080/a',
            'GET',
            [
                'allow_redirects' => false,
                'on_headers' => static function (): void {
                    throw new RuntimeException('expected_probe_callback_failure');
                },
            ],
            'probe'
        );
    } catch (Throwable $e) {
        $caught = true;
    }
    check($caught, 'callback_failure_propagates');
    released('callback_failure_handler_teardown');
    $versions = [];
    foreach ([
        'typo3/cms-core',
        'guzzlehttp/guzzle',
        'guzzlehttp/promises',
        'guzzlehttp/psr7',
    ] as $package) {
        $versions[$package] = Composer\InstalledVersions::getPrettyVersion($package);
    }
    echo json_encode(
        [
            'status' => 'PASS',
            'fixture' => $fixture,
            'case' => $bootCase,
            'versions' => $versions,
            'php' => PHP_VERSION,
            'curl' => curl_version()['version'],
            'checks' => $checks,
            'attempts' => State::$attempts,
            'released' => State::$released,
            'observedPrimaryIps' => State::$observedIps,
            'plans' => State::$plans,
            'peakConcurrentLeases' => State::$peakLeases,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ) . "\n";
} catch (Throwable $e) {
    echo json_encode(
        [
            'status' => 'FAIL',
            'fixture' => $fixture,
            'case' => $bootCase,
            'errorClass' => $e::class,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'checks' => $checks,
            'middlewareTrace' => State::$trace,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ) . "\n";
    exit(1);
}
