<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\{Create, PromiseInterface, Utils};
use GuzzleHttp\Psr7\{Request, Response};
use Netresearch\HttpGuard\{AddressClassifier, GuardConfig, PolicyEngine, PolicyException, PolicyRegistry, SystemClock, TargetNormalizer};
use Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures\{LifecycleReporter, LifecycleResolver};
use Netresearch\HttpGuard\Transport\{GuardedTransferFactory, InvocationRegistry, TerminalGuardMiddleware, TransferDriver};
use PHPUnit\Framework\Attributes\{DataProvider, PreserveGlobalState, RunTestsInSeparateProcesses};
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class TerminalMutationBoundaryTest extends TestCase
{
    private array $proxies = [];

    protected function setUp(): void
    {
        require_once __DIR__ . '/Fixtures/TransferLifecycleHarness.php';
        foreach (getenv(null, true) as $name => $value) {
            if (in_array(strtolower((string) $name), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                $this->proxies[$name] = $value;
                putenv((string) $name);
            }
        }
    }

    protected function tearDown(): void
    {
        Utils::queue()->run();
        foreach ($this->proxies as $name => $value) {
            putenv($name . '=' . $value);
        }
    }

    public function testObservePreservesARecognizedStackWithoutAFalseUnverifiableDecision(): void
    {
        [$terminal, $invocations, $context, $driver, $reporter] = $this->terminal('observe');
        $stack                                                  = HandlerStack::create();
        $request                                                = new Request('GET', 'https://public.example');
        $response                                               = new Response(204);
        $options                                                = ['handler' => $stack];
        $handle                                                 = $terminal(
            static function ($actualRequest, $actualOptions) use ($request, $options, $response) {
                self::assertSame($request, $actualRequest);
                self::assertSame($options, $actualOptions);

                return Create::promiseFor($response);
            },
        );
        self::assertSame($response, $handle($request, $options)->wait());
        self::assertSame(['allow'], array_column($reporter->events, 'decision'));
        self::assertSame(0, $driver->counters()['created']);
        self::assertSame(0, $invocations->activeCount());
    }

    public function testObserveReportsAnUnsupportedRuntimeButPreservesCallerDispatch(): void
    {
        [$terminal, , , $driver, $reporter] = $this->terminal('observe');
        $this->unsupportedMetadata();
        $response = new Response(202);
        $handle   = $terminal(static fn () => Create::promiseFor($response));
        self::assertSame($response, $handle(new Request('GET', 'https://public.example'), [])->wait());
        self::assertSame(['allow', 'unverifiable'], array_column($reporter->events, 'decision'));
        self::assertSame('transport_unsupported', $reporter->events[1]->reasonCode);
        self::assertSame(0, $driver->counters()['created']);
    }

    public function testEnforceRejectsUnsupportedRuntimeBeforeAllocatingALease(): void
    {
        [$terminal, $invocations, $context, $driver, $reporter] = $this->terminal();
        [$envelope, $token]                                     = $invocations->open($context, 'https://public.example', 'https', null);
        $this->unsupportedMetadata();
        $handle = $terminal(
            static function (): never {
                self::fail('Terminal must own dispatch');
            },
        );
        $promise = $handle(
            new Request('GET', 'https://public.example'),
            [
                'nr_http_guard_context'    => $context,
                'nr_http_guard_envelope'   => $envelope,
                'nr_http_guard_invocation' => $token,
            ],
        );
        $this->assertDenial($promise, 'transport_unsupported');
        self::assertSame(0, $invocations->activeCount());
        self::assertSame(0, $driver->counters()['created']);
        self::assertSame(['deny'], array_column($reporter->events, 'decision'));
    }

    #[DataProvider('unsafePublicRequests')]
    public function testPublicFetchChecksEachIndependentRequestRestriction(string $method, string $body): void
    {
        [$terminal, $invocations, $context, $driver, $reporter] = $this->terminal();
        [$envelope, $token]                                     = $invocations->open($context, 'https://public.example', 'https', null, true);
        $promise                                                = $terminal(
            static function (): never {
                self::fail('Terminal must own dispatch');
            },
        )(
            new Request($method, 'https://public.example', [], $body),
            [
                'nr_http_guard_context'    => $context,
                'nr_http_guard_envelope'   => $envelope,
                'nr_http_guard_invocation' => $token,
            ]
        );
        $this->assertDenial($promise, 'option_forbidden');
        self::assertSame(0, $invocations->activeCount());
        self::assertSame(0, $driver->counters()['created']);
        self::assertSame(['option_forbidden'], array_column($reporter->events, 'reasonCode'));
    }

    public static function unsafePublicRequests(): iterable
    {
        yield 'method alone' => ['POST', ''];
        yield 'body alone' => ['GET', 'payload'];
    }

    private function assertDenial(PromiseInterface $promise, string $expected): void
    {
        $reason = null;
        $promise->then(
            null,
            static function ($error) use (&$reason): void {
                $reason = $error;
            },
        );
        Utils::queue()->run();
        self::assertSame(PromiseInterface::REJECTED, $promise->getState());
        self::assertInstanceOf(PolicyException::class, $reason);
        self::assertSame($expected, $reason->reasonCode());
    }

    private function unsupportedMetadata(): void
    {
        $data                                                    = InstalledVersions::getAllRawData()[0];
        $data['versions']['guzzlehttp/guzzle']['pretty_version'] = '99.0.0';
        $loaders                                                 = ClassLoader::getRegisteredLoaders();
        InstalledVersions::reload($data);
        (new ReflectionProperty(InstalledVersions::class, 'installedByVendor'))->setValue(
            null,
            array_fill_keys(array_keys($loaders), $data),
        );
    }

    private function terminal(string $mode = 'enforce'): array
    {
        $config      = GuardConfig::fromArray(['mode' => $mode]);
        $clock       = new SystemClock();
        $registry    = new PolicyRegistry($config, $clock);
        $context     = $registry->newContext();
        $resolver    = new LifecycleResolver();
        $reporter    = new LifecycleReporter();
        $engine      = new PolicyEngine(new TargetNormalizer(), new AddressClassifier(), $resolver, $registry, $clock, $reporter);
        $invocations = new InvocationRegistry();
        $driver      = new TransferDriver();

        return [
            new TerminalGuardMiddleware(
                $engine,
                $config,
                $context,
                $invocations,
                new GuardedTransferFactory($engine, $context, $driver),
            ),
            $invocations,
            $context,
            $driver,
            $reporter,
        ];
    }
}
