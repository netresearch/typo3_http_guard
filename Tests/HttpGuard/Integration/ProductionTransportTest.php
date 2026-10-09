<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Tests\Integration;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Netresearch\HttpGuard\{AddressClassifier, ClockInterface, GuardConfig, NullDecisionReporter, PolicyEngine, PolicyException, PolicyRegistry, Resolution, ResolverInterface, TargetNormalizer};
use Netresearch\HttpGuard\Client\{ClientStackConfiguration, ClientStackProviderInterface, GuardedClientFactory};
use Netresearch\HttpGuard\Transport\{BoundaryMiddleware, TerminalGuardMiddleware};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{RequestInterface, ResponseInterface, StreamInterface};

final class TransportClock implements ClockInterface
{
    public float $elapsed = 0;
    public function now(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('2026-10-08T00:00:00Z'))->modify(
            '+' . (int) $this->elapsed . ' seconds'
        );
    }
    public function monotonic(): float
    {
        return $this->elapsed;
    }
}
final class TransportResolver implements ResolverInterface
{
    public int $calls = 0;
    public ?\Closure $after = null;
    public array $answers = [];
    public function resolve(string $host): Resolution
    {
        ++$this->calls;
        $answers = $this->answers[$host] ?? [['203.0.115.100']];
        $addresses = $answers[min($this->calls - 1, count($answers) - 1)];
        ($this->after ?? static function (): void {
        })();
        return new Resolution($addresses, 'synthetic', 0, (string) $this->calls);
    }
}
final class TransportStackProvider implements ClientStackProviderInterface
{
    public function __construct(
        private array $middlewares = [],
        private array $defaults = [],
        private ?\Closure $leaf = null
    )
    {
    }
    public function create(
        BoundaryMiddleware $boundary,
        TerminalGuardMiddleware $terminal
    ): ClientStackConfiguration
    {
        $stack = HandlerStack::create($this->leaf);
        $stack->push($boundary, 'nr/http-guard-boundary');
        foreach ($this->middlewares as $i => $middleware) {
            $stack->push($middleware, 'fixture_' . $i);
        }
        $stack->push($terminal, 'nr/http-guard-terminal');
        return new ClientStackConfiguration($stack, $this->defaults);
    }
}
final class PreparationStream implements StreamInterface
{
    use StreamDecoratorTrait;
    public function __construct(
        StreamInterface $stream,
        private readonly \Closure $duringRead
    )
    {
        $this->stream = $stream;
    }
    public function getContents(): string
    {
        ($this->duringRead)();
        return $this->stream->getContents();
    }
    private StreamInterface $stream;
    public function read(int $length): string
    {
        ($this->duringRead)();
        return $this->stream->read($length);
    }
}

final class ProductionTransportTest extends TestCase
{
    private array $proxyEnvironment = [];
    protected function setUp(): void
    {
        foreach (getenv(null, true) as $key => $value) {
            if (in_array(
                strtolower((string) $key),
                ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'],
                true
            )) {
                $this->proxyEnvironment[$key] = $value;
                putenv((string) $key);
            }
        }
    }
    protected function tearDown(): void
    {
        foreach (getenv(null, true) as $key => $value) {
            if (in_array(
                strtolower((string) $key),
                ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'],
                true
            )) {
                putenv((string) $key);
            }
        }
        foreach ($this->proxyEnvironment as $key => $value) {
            putenv($key . '=' . $value);
        }
    }
    private function fixture(
        array $input = [],
        ?TransportResolver $resolver = null,
        ?TransportStackProvider $provider = null,
        ?TransportClock $clock = null
    ): array
    {
        $config = GuardConfig::fromArray($input);
        $clock ??= new TransportClock();
        $registry = new PolicyRegistry($config, $clock);
        $resolver ??= new TransportResolver();
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            new NullDecisionReporter()
        );
        return [
            new GuardedClientFactory($engine, $config, $registry, $provider),
            $resolver,
            $clock,
            $registry,
        ];
    }
    private function endpoint(array $extra = []): array
    {
        return array_replace_recursive(
            [
                'endpoints' => [
                    'erp' => [
                        'origin' => 'http://erp.test:8090',
                        'allowedCidrs' => ['10.23.4.12/32'],
                        'methods' => ['POST'],
                        'purpose' => 'Synthetic integration',
                        'owner' => 'Test maintainers',
                    ],
                ],
            ],
            $extra
        );
    }
    private function counters(): array
    {
        $all = [];
        foreach ([
            'public-a' => '203.0.115.100',
            'public-b' => '203.0.115.101',
            'private' => '10.23.4.12',
            'loopback' => '127.0.0.1',
        ] as $label => $ip) {
            $handle = curl_init('http://' . $ip . ':' . ($label === 'loopback' ? '18091' : '8091') . '/stats');
            curl_setopt_array(
                $handle,
                [
                    CURLOPT_PROXY => '',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 2,
                ]
            );
            $data = curl_exec($handle);
            if (!is_string($data)) {
                throw new \RuntimeException(
                    'Start tests/Integration/prepare-wire.sh before running the suite'
                );
            }
            $all[$label] = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }
        return $all;
    }
    private function zeroContact(callable $call, string $reason): void
    {
        $before = $this->counters();
        try {
            $call();
            self::fail('Unprotected target accepted');
        } catch (PolicyException $error) {
            self::assertSame($reason, $error->reasonCode());
        }
        $after = $this->counters();
        $this->assertNoContact($before, $after);
    }
    /**
     * Compare cumulative contact counters; earlier connection cleanup may change lifecycle counters.
     */
    private function assertNoContact(array $before, array $after): void
    {
        foreach ($before as $label => $count) {
            self::assertSame(
                $count['tcp'],
                $after[$label]['tcp'],
                $label . ' TCP contact'
            );
            self::assertSame(
                $count['requests'],
                $after[$label]['requests'],
                $label . ' HTTP contact'
            );
        }
    }
    public function testCanonicalPinnedTargetCallbacksAndTeardown(): void
    {
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $headersArgs = [];
        $stats = null;
        $response = $binding->client->request(
            'GET',
            'http://GUARD.test.:8090/echo',
            [
                'timeout' => 0,
                'connect_timeout' => 0,
                'on_headers' => static function (...$args) use (&$headersArgs): void {
                    $headersArgs = $args;
                },
                'on_stats' => static function ($value) use (&$stats): void {
                    $stats = $value;
                },
            ]
        );
        $echo = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('public-a', $echo['label']);
        self::assertSame('guard.test:8090', $echo['host']);
        self::assertCount(
            \GuzzleHttp\ClientInterface::MAJOR_VERSION === 8 ? 2 : 1,
            $headersArgs
        );
        self::assertSame('203.0.115.100', $stats->getHandlerStat('primary_ip'));
        self::assertSame(
            [
                'created' => 1,
                'released' => 1,
                'active' => 0,
                'peak' => 1,
                'nativeConstructed' => 1,
            ],
            $binding->driver->counters()
        );
    }
    public function testMixedAnswersDenyWholeAttemptBeforeNativeTransport(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['guard.test'] = [['203.0.115.100', '10.23.4.12']];
        [$factory] = $this->fixture([], $resolver);
        $binding = $factory->createTransport();
        $this->zeroContact(
            fn() => $binding->client->request('GET', 'http://guard.test:8090/echo'),
            'address_forbidden'
        );
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
    }
    public function testExactEndpointWorksWithoutGrantingPublicImporter(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['erp.test'] = [['10.23.4.12']];
        [$factory] = $this->fixture($this->endpoint(), $resolver);
        $request = new Request(
            'POST',
            'http://erp.test:8090/echo',
            ['X-Synthetic-Secret' => 'test-only'],
            'body-secret'
        );
        $this->zeroContact(
            fn() => $factory->publicClient()->sendRequest($request),
            'address_forbidden'
        );
        $response = $factory->forEndpoint('erp')->sendRequest($request);
        $echo = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('private', $echo['label']);
        self::assertSame(base64_encode('body-secret'), $echo['body']);
        $this->zeroContact(
            fn() => $factory
                ->forEndpoint('erp')
                ->sendRequest(new Request('GET', 'http://erp.test:8090/echo')),
            'endpoint_mismatch'
        );
        $this->zeroContact(
            fn() => $factory
                ->forEndpoint('erp')
                ->sendRequest(new Request('POST', 'http://erp.test:8092/echo')),
            'endpoint_mismatch'
        );
        $this->zeroContact(
            fn() => $factory
                ->forEndpoint('erp')
                ->sendRequest(new Request('POST', 'http://guard.test:8090/echo')),
            'endpoint_mismatch'
        );
    }
    public function testOperatorDenyWinsAndHeadersCannotGrant(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['erp.test'] = [['10.23.4.12']];
        [$factory] = $this->fixture(
            $this->endpoint(['deniedCidrs' => ['10.23.4.12/32']]),
            $resolver
        );
        $this->zeroContact(
            fn() => $factory
                ->forEndpoint('erp')
                ->sendRequest(new Request('POST', 'http://erp.test:8090/echo')),
            'address_forbidden'
        );
        [$factory] = $this->fixture($this->endpoint(), $resolver);
        $this->zeroContact(
            fn() => $factory
                ->publicClient()
                ->sendRequest(
                    new Request(
                        'POST',
                        'http://erp.test:8090/echo',
                        ['nr_http_guard_grant' => 'erp']
                    )
                ),
            'address_forbidden'
        );
        $this->zeroContact(
            fn() => $factory->createTransport()->client->request(
                'POST',
                'http://erp.test:8090/echo',
                ['nr_http_guard_grant' => 'erp']
            ),
            'grant_invalid'
        );
    }
    public function testQueueExpiryAndPreStartCancellationHaveNoContact(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['erp.test'] = [['10.23.4.12']];
        [$factory, $resolver, $clock] = $this->fixture(
            $this->endpoint(
                [
                    'endpoints' => ['erp' => ['expiresAt' => '2026-10-08T00:00:02Z']],
                ]
            ),
            $resolver
        );
        $binding = $factory->createTransport([], 'erp');
        $pending = $binding->client->requestAsync('POST', 'http://erp.test:8090/echo');
        self::assertSame(0, $resolver->calls);
        $clock->elapsed = 2;
        $this->zeroContact(fn() => $pending->wait(), 'grant_invalid');
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $before = $this->counters();
        $pending = $binding->client->requestAsync('GET', 'http://guard.test:8090/echo');
        $pending->cancel();
        $binding->driver->tick();
        self::assertSame(PromiseInterface::REJECTED, $pending->getState());
        $this->assertNoContact($before, $this->counters());
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
        self::assertSame(0, $binding->driver->counters()['active']);
    }
    public function testExpiryDuringDnsAndBodyPreparationPreventsFirstConnection(): void
    {
        foreach (['dns', 'body'] as $stage) {
            $resolver = new TransportResolver();
            $resolver->answers['erp.test'] = [['10.23.4.12']];
            [$factory, $resolver, $clock] = $this->fixture(
                $this->endpoint(
                    [
                        'endpoints' => ['erp' => ['expiresAt' => '2026-10-08T00:00:02Z']],
                    ]
                ),
                $resolver
            );
            $binding = $factory->createTransport([], 'erp');
            if ($stage === 'dns') {
                $resolver->after = static function () use ($clock): void {
                    $clock->elapsed = 2;
                };
                $body = 'hello';
            } else {
                $body = new PreparationStream(
                    Utils::streamFor('hello'),
                    static function () use ($clock): void {
                        $clock->elapsed = 2;
                    }
                );
            }
            $this->zeroContact(
                fn() => $binding->client->send(
                    new Request('POST', 'http://erp.test:8090/echo', [], $body)
                ),
                'grant_invalid'
            );
            self::assertSame(0, $binding->driver->counters()['active']);
        }
    }
    public function testRealRetryMiddlewareGetsFreshPlanAndKeepsInvocation(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['guard.test'] = [['203.0.115.199'], ['203.0.115.100']];
        $retry = Middleware::retry(
            static fn(
                int $retries,
                $request,
                $response,
                $error
            ): bool => $retries === 0 && $error !== null && !$error instanceof PolicyException,
            static fn(): int => 0
        );
        [$factory] = $this->fixture([], $resolver, new TransportStackProvider([$retry]));
        $binding = $factory->createTransport(['connect_timeout' => 0.05, 'timeout' => 0.2]);
        $response = $binding->client->request('GET', 'http://guard.test:8090/echo');
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $resolver->calls);
        self::assertSame(2, $binding->driver->counters()['nativeConstructed']);
        self::assertSame(0, $binding->driver->counters()['active']);
    }
    public function testRetryAfterGrantExpiryCannotContactAgain(): void
    {
        $clock = new TransportClock();
        $resolver = new TransportResolver();
        $resolver->answers['erp.test'] = [['203.0.115.199']];
        $retry = Middleware::retry(
            static function (
                int $retries,
                $request,
                $response,
                $error
            ) use ($clock): bool {
                $clock->elapsed = 2;
                return $retries === 0 && $error !== null && !$error instanceof PolicyException;
            },
            static fn(): int => 0
        );
        $config = $this->endpoint(
            [
                'endpoints' => [
                    'erp' => [
                        'allowedCidrs' => ['203.0.115.199/32'],
                        'expiresAt' => '2026-10-08T00:00:02Z',
                    ],
                ],
            ]
        );
        [$factory] = $this->fixture(
            $config,
            $resolver,
            new TransportStackProvider([$retry]),
            $clock
        );
        $binding = $factory->createTransport(
            ['connect_timeout' => 0.05, 'timeout' => 0.2],
            'erp'
        );
        $this->zeroContact(
            fn() => $binding->client->request('POST', 'http://erp.test:8090/echo'),
            'grant_invalid'
        );
        self::assertSame(1, $resolver->calls);
        self::assertSame(1, $binding->driver->counters()['nativeConstructed']);
    }
    public function testIntermediateOriginRewriteFailsBeforeWire(): void
    {
        $rewrite = static fn(
            callable $next
        ) => static fn(RequestInterface $request, array $options) => $next(
            $request->withUri($request->getUri()->withHost('other.test')),
            $options
        );
        [$factory] = $this->fixture([], null, new TransportStackProvider([$rewrite]));
        $this->zeroContact(
            fn() => $factory->createTransport()->client->request(
                'GET',
                'http://guard.test:8090/echo'
            ),
            'authority_mismatch'
        );
    }
    public function testConcurrentEnvelopeOnlySwapAndClosedReplayFail(): void
    {
        $captured = null;
        $replay = false;
        $tamper = static function (callable $next) use (&$captured, &$replay): callable {
            return static function (
                RequestInterface $request,
                array $options
            ) use ($next, &$captured, &$replay) {
                if ($captured === null) {
                    $captured = $options;
                } elseif ($replay) {
                    foreach ([
                        'nr_http_guard_envelope',
                        'nr_http_guard_invocation',
                        'nr_http_guard_context',
                    ] as $key) {
                        $options[$key] = $captured[$key];
                    }
                } else {
                    $options['nr_http_guard_envelope'] = $captured['nr_http_guard_envelope'];
                }
                return $next($request, $options);
            };
        };
        [$factory] = $this->fixture([], null, new TransportStackProvider([$tamper]));
        $binding = $factory->createTransport();
        $first = $binding->client->requestAsync('GET', 'http://guard.test:8090/echo');
        $this->zeroContact(
            fn() => $binding->client
                ->requestAsync('GET', 'http://guard.test:8090/echo')
                ->wait(),
            'grant_invalid'
        );
        $first->cancel();
        $binding->driver->tick();
        $replay = true;
        $this->zeroContact(
            fn() => $binding->client->request('GET', 'http://guard.test:8090/echo'),
            'grant_invalid'
        );
    }
    public function testSameOriginRedirectAndPsr18NoFollow(): void
    {
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $response = $binding->client->request('GET', 'http://guard.test:8090/redirect-same');
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $binding->driver->counters()['created']);
        $before = $this->counters();
        $response = $factory
            ->publicClient()
            ->sendRequest(new Request('GET', 'http://guard.test:8090/redirect-same'));
        self::assertSame(302, $response->getStatusCode());
        self::assertSame(
            $before['public-a']['requests'] + 1,
            $this->counters()['public-a']['requests']
        );
    }
    public function testLateResponseLocationAndSecretCrossOriginCannotFollow(): void
    {
        $rewrite = static fn(callable $next) => static fn(
            RequestInterface $request,
            array $options
        ) => $next($request, $options)->then(
            static fn(ResponseInterface $response) => $response
                ->withStatus(302)
                ->withHeader('Location', 'http://erp.test:8090/echo')
        );
        [$factory] = $this->fixture([], null, new TransportStackProvider([$rewrite]));
        $before = $this->counters();
        try {
            $factory->createTransport()->client->request(
                'POST',
                'http://guard.test:8090/echo',
                [
                    'body' => 'synthetic-secret',
                    'headers' => ['X-Secret' => 'synthetic-secret'],
                ]
            );
            self::fail('Cross-origin follow accepted');
        } catch (PolicyException $error) {
            self::assertSame('redirect_forbidden', $error->reasonCode());
        }
        $after = $this->counters();
        self::assertSame(
            $before['public-a']['requests'] + 1,
            $after['public-a']['requests']
        );
        self::assertSame($before['private']['tcp'], $after['private']['tcp']);
        self::assertSame($before['public-b']['tcp'], $after['public-b']['tcp']);
    }
    public function testPublicFetchScrubsInheritedCredentialsAcrossOrigins(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['guard.test'] = [['203.0.115.100']];
        $resolver->answers['other.test'] = [['203.0.115.101']];
        $provider = new TransportStackProvider(
            [],
            [
                'headers' => [
                    'Authorization' => 'synthetic-secret',
                    'X-Secret' => 'synthetic-secret',
                ],
                'auth' => ['synthetic-user', 'synthetic-pass'],
                'cert' => '/nonexistent-secret-client-certificate',
                'ssl_key' => '/nonexistent-secret-client-key',
                'cookies' => new \GuzzleHttp\Cookie\CookieJar(),
            ]
        );
        [$factory] = $this->fixture([], $resolver, $provider);
        $echo = json_decode(
            (string) $factory
                ->publicFetch()
                ->fetch(new Uri('http://guard.test:8090/redirect-cross'))
                ->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('public-b', $echo['label']);
        self::assertSame('GET', $echo['method']);
        self::assertSame('', $echo['body']);
        self::assertArrayNotHasKey('Authorization', $echo['headers']);
        self::assertArrayNotHasKey('X-Secret', $echo['headers']);
        self::assertArrayNotHasKey('Cookie', $echo['headers']);
    }
    public function testTlsHostSniCustomCaAndMutualTls(): void
    {
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $cert = __DIR__ . '/certificates/';
        $response = $binding->client->request(
            'GET',
            'https://guard.test:8443/echo',
            ['verify' => $cert . 'ca.crt']
        );
        $echo = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('guard.test:8443', $echo['host']);
        $response = $binding->client->request(
            'GET',
            'https://guard.test:8444/echo',
            [
                'verify' => $cert . 'ca.crt',
                'cert' => $cert . 'client.crt',
                'ssl_key' => $cert . 'client.key',
            ]
        );
        self::assertSame(200, $response->getStatusCode());
        $stats = $this->counters()['public-a'];
        self::assertContains('guard.test', $stats['sni']);
        self::assertNotEmpty($stats['clients']);
    }
    public function testActiveCancellationAndCallbackErrorReleaseTransport(): void
    {
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $headers = false;
        $pending = $binding->client->requestAsync(
            'GET',
            'http://guard.test:8090/slow',
            [
                'on_headers' => static function () use (&$headers): void {
                    $headers = true;
                },
            ]
        );
        $deadline = microtime(true) + 2;
        while (!$headers && microtime(true) < $deadline) {
            $binding->driver->tick();
        }
        self::assertTrue($headers);
        $pending->cancel();
        $binding->driver->tick();
        self::assertSame(0, $binding->driver->counters()['active']);
        $deadline = microtime(true) + 2;
        do {
            $stats = $this->counters()['public-a'];
            if ($stats['active'] === 0) {
                break;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        self::assertSame(0, $stats['active']);
        try {
            $binding->client->request(
                'GET',
                'http://guard.test:8090/echo',
                [
                    'on_headers' => static function (): void {
                        throw new \RuntimeException('synthetic-callback');
                    },
                ]
            );
            self::fail('Callback error ignored');
        } catch (\Throwable $error) {
            self::assertStringContainsString(
                'synthetic-callback',
                $error->getMessage() . ($error->getPrevious()?->getMessage() ?? '')
            );
        }
        self::assertSame(0, $binding->driver->counters()['active']);
    }
    public function testParallelSameAuthorityPlansCannotContaminate(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['guard.test'] = [
            ['203.0.115.100'],
            ['203.0.115.101'],
            ['203.0.115.100'],
            ['203.0.115.101'],
        ];
        [$factory] = $this->fixture([], $resolver);
        $binding = $factory->createTransport();
        $before = $this->counters();
        $pending = [];
        for ($i = 0; $i < 4; ++$i) {
            $pending[] = $binding->client->requestAsync('GET', 'http://guard.test:8090/slow');
        }
        $deadline = microtime(true) + 4;
        do {
            $binding->driver->tick();
            $waiting = array_filter(
                $pending,
                static fn($p) => $p->getState() === PromiseInterface::PENDING
            );
        } while ($waiting !== [] && microtime(true) < $deadline);
        foreach ($pending as $p) {
            self::assertSame(200, $p->wait()->getStatusCode());
        }
        $after = $this->counters();
        self::assertSame(
            $before['public-a']['requests'] + 2,
            $after['public-a']['requests']
        );
        self::assertSame(
            $before['public-b']['requests'] + 2,
            $after['public-b']['requests']
        );
        self::assertSame(4, $binding->driver->counters()['nativeConstructed']);
        self::assertSame(4, $binding->driver->counters()['peak']);
        self::assertSame(0, $binding->driver->counters()['active']);
    }
    public function testPolicyFailureCannotBeRetriedEvenByIndiscriminateMiddleware(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['guard.test'] = [['203.0.115.100', '10.23.4.12'], ['203.0.115.100']];
        $retry = Middleware::retry(
            static fn(
                int $retries,
                $request,
                $response,
                $error
            ): bool => $retries === 0 && $error !== null,
            static fn(): int => 0
        );
        [$factory] = $this->fixture([], $resolver, new TransportStackProvider([$retry]));
        $binding = $factory->createTransport();
        $before = $this->counters();
        try {
            $binding->client->request('GET', 'http://guard.test:8090/echo');
            self::fail('A denied attempt was retried into success');
        } catch (PolicyException) {
        }
        self::assertSame(1, $resolver->calls);
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
        $after = $this->counters();
        foreach ($before as $label => $stats) {
            self::assertSame($stats['tcp'], $after[$label]['tcp']);
            self::assertSame($stats['requests'], $after[$label]['requests']);
        }
    }
    public function testApprovedMultiAddressPinAndFailedPinNeverUseSecondResolver(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['guard.test'] = [['203.0.115.1', '203.0.115.100'], ['10.23.4.12']];
        [$factory] = $this->fixture([], $resolver);
        $binding = $factory->createTransport(['connect_timeout' => 0.3, 'timeout' => 1]);
        $before = $this->counters();
        $response = $binding->client->request('GET', 'http://guard.test:8090/echo');
        $echo = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('public-a', $echo['label']);
        self::assertSame(1, $resolver->calls);
        self::assertSame(1, $binding->driver->counters()['nativeConstructed']);
        $after = $this->counters();
        self::assertSame(
            $before['public-a']['requests'] + 1,
            $after['public-a']['requests']
        );
        self::assertSame($before['private']['tcp'], $after['private']['tcp']);
        $resolver = new TransportResolver();
        $resolver->answers['guard.test'] = [['203.0.115.1'], ['10.23.4.12']];
        [$factory] = $this->fixture([], $resolver);
        $binding = $factory->createTransport(['connect_timeout' => 0.1, 'timeout' => 0.2]);
        $before = $this->counters();
        try {
            $binding->client->request('GET', 'http://guard.test:8090/echo');
            self::fail('Failed pin fell back');
        } catch (\GuzzleHttp\Exception\ConnectException) {
        }
        self::assertSame(1, $resolver->calls);
        $this->assertNoContact($before, $this->counters());
        self::assertSame(0, $binding->driver->counters()['active']);
    }
    public function testEveryDeniedAddressCorpusBoundaryHasZeroNativeAndWireContact(): void
    {
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $before = $this->counters();
        $corpus = json_decode(
            file_get_contents(
                dirname(__DIR__, 3) . '/Resources/Private/HttpGuard/data/security-corpus/address-cases.json'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $tested = 0;
        foreach ($corpus['cases'] as $case) {
            if ($case['expected']['public_decision'] !== 'deny') {
                continue;
            }
            $ip = $case['input']['address'];
            $uri = 'http://' . (str_contains($ip, ':') ? '[' . $ip . ']' : $ip) . ':8090/echo';
            try {
                $binding->client->request('GET', $uri);
                self::fail('Denied literal accepted: ' . $case['id']);
            } catch (PolicyException $error) {
                self::assertSame(
                    'address_forbidden',
                    $error->reasonCode(),
                    $case['id']
                );
            }
            ++$tested;
        }
        self::assertGreaterThan(150, $tested);
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
        $this->assertNoContact($before, $this->counters());
    }
    public function testRawRoutingSharingProxyAndStreamOptionsFailBeforeContact(): void
    {
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $raw = [
            CURLOPT_RESOLVE,
            CURLOPT_CONNECT_TO,
            CURLOPT_URL,
            CURLOPT_SHARE,
            CURLOPT_UNIX_SOCKET_PATH,
            CURLOPT_FOLLOWLOCATION,
            CURLOPT_ALTSVC,
            CURLOPT_ALTSVC_CTRL,
            CURLOPT_HSTS,
            CURLOPT_HSTS_CTRL,
            CURLOPT_HTTP_VERSION,
            CURLOPT_PROXY,
        ];
        foreach ($raw as $option) {
            $this->zeroContact(
                fn() => $binding->client->request(
                    'GET',
                    'http://guard.test:8090/echo',
                    ['curl' => [$option => 'synthetic']]
                ),
                'option_forbidden'
            );
        }
        foreach ([
            ['stream' => true],
            ['stream_context' => []],
            ['delay' => 1],
            ['transport_sharing' => 'persistent'],
            ['unknown-route' => 'synthetic'],
        ] as $options) {
            $this->zeroContact(
                fn() => $binding->client->request(
                    'GET',
                    'http://guard.test:8090/echo',
                    $options
                ),
                'option_forbidden'
            );
        }
        foreach ([
            'http://203.0.115.101:8090',
            'https://203.0.115.101:8443',
            'socks5://203.0.115.101:8090',
        ] as $proxy) {
            $this->zeroContact(
                fn() => $binding->client->request(
                    'GET',
                    'http://guard.test:8090/echo',
                    ['proxy' => $proxy]
                ),
                'proxy_unsupported'
            );
        }
        foreach ([
            'HTTP_PROXY',
            'hTtP_pRoXy',
            'https_proxy',
            'ALL_PROXY',
            'no_proxy',
            'NO_PROXY',
        ] as $name) {
            putenv($name . '=synthetic');
            try {
                $this->zeroContact(
                    fn() => $binding->client->request(
                        'GET',
                        'http://guard.test:8090/echo',
                        ['proxy' => '']
                    ),
                    'proxy_unsupported'
                );
            } finally {
                putenv($name);
            }
        }
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
    }
    public function testProxyActivatedDuringPreparationCannotCauseSilentDirectBypass(): void
    {
        $resolver = new TransportResolver();
        $resolver->after = static function (): void {
            putenv('HTTP_PROXY=http://203.0.115.101:8090');
        };
        [$factory] = $this->fixture([], $resolver);
        $binding = $factory->createTransport();
        try {
            $this->zeroContact(
                fn() => $binding->client->request('GET', 'http://guard.test:8090/echo'),
                'proxy_unsupported'
            );
        } finally {
            putenv('HTTP_PROXY');
        }
        self::assertSame(0, $binding->driver->counters()['active']);
    }
    public function testParallelScopesAndLongLivedWorkerNeverInheritEndpointGrant(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['guard.test'] = [['203.0.115.100'], ['10.23.4.12'], ['10.23.4.12']];
        $config = $this->endpoint(
            [
                'endpoints' => [
                    'erp' => [
                        'origin' => 'http://guard.test:8090',
                        'methods' => ['POST'],
                    ],
                ],
            ]
        );
        [$factory] = $this->fixture($config, $resolver);
        $public = $factory->createTransport();
        $internal = $factory->createTransport([], 'erp');
        $before = $this->counters();
        $a = $public->client->requestAsync('GET', 'http://guard.test:8090/echo');
        $b = $internal->client->requestAsync('POST', 'http://guard.test:8090/echo');
        $deadline = microtime(true) + 2;
        do {
            $public->driver->tick();
            $internal->driver->tick();
        } while (($a->getState() === PromiseInterface::PENDING || $b->getState() === PromiseInterface::PENDING) && microtime(true) < $deadline);
        self::assertSame(
            'public-a',
            json_decode(
                (string) $a->wait()->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            )['label']
        );
        self::assertSame(
            'private',
            json_decode(
                (string) $b->wait()->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            )['label']
        );
        $after = $this->counters();
        self::assertSame(
            $before['public-a']['requests'] + 1,
            $after['public-a']['requests']
        );
        self::assertSame(
            $before['private']['requests'] + 1,
            $after['private']['requests']
        );
        $this->zeroContact(
            fn() => $public->client->request('GET', 'http://guard.test:8090/echo'),
            'address_forbidden'
        );
        self::assertSame(1, $public->driver->counters()['nativeConstructed']);
        self::assertSame(1, $internal->driver->counters()['nativeConstructed']);
    }
    public function testRedirectMethodsLimitsDowngradeAndNoFollowRemainBound(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['erp.test'] = [['10.23.4.12']];
        $config = $this->endpoint(
            ['endpoints' => ['erp' => ['redirects' => 'same-origin']]]
        );
        [$factory] = $this->fixture($config, $resolver);
        $binding = $factory->createTransport([], 'erp');
        $before = $this->counters();
        try {
            $binding->client->request(
                'POST',
                'http://erp.test:8090/redirect-post-get',
                ['body' => 'synthetic-secret']
            );
            self::fail('POST-only profile permitted GET redirect');
        } catch (PolicyException $error) {
            self::assertSame('endpoint_mismatch', $error->reasonCode());
        }
        $after = $this->counters();
        self::assertSame(
            $before['private']['requests'] + 1,
            $after['private']['requests']
        );
        [$factory] = $this->fixture(['redirects' => ['max' => 2]]);
        $binding = $factory->createTransport();
        $this->zeroContact(
            fn() => $binding->client->request(
                'GET',
                'http://guard.test:8090/loop',
                ['allow_redirects' => ['max' => 3]]
            ),
            'redirect_forbidden'
        );
        $before = $this->counters();
        try {
            $binding->client->request(
                'GET',
                'http://guard.test:8090/loop',
                ['allow_redirects' => ['max' => 1]]
            );
            self::fail('Loop accepted');
        } catch (\GuzzleHttp\Exception\TooManyRedirectsException) {
        }
        $after = $this->counters();
        self::assertSame(
            $before['public-a']['requests'] + 2,
            $after['public-a']['requests']
        );
        $cert = __DIR__ . '/certificates/ca.crt';
        $before = $this->counters();
        try {
            $binding->client->request(
                'GET',
                'https://guard.test:8443/redirect-downgrade',
                ['verify' => $cert]
            );
            self::fail('Downgrade accepted');
        } catch (PolicyException $error) {
            self::assertSame('redirect_forbidden', $error->reasonCode());
        }
        $after = $this->counters();
        self::assertSame(
            $before['public-a']['requests'] + 1,
            $after['public-a']['requests']
        );
        self::assertSame(
            302,
            $binding->client
                ->request(
                    'GET',
                    'http://guard.test:8090/redirect-cross',
                    ['allow_redirects' => false]
                )
                ->getStatusCode()
        );
    }
    public function testCancellationDuringResolverAndBodyReadStopsFirstNativeContact(): void
    {
        foreach (['dns', 'body'] as $stage) {
            $resolver = new TransportResolver();
            [$factory] = $this->fixture([], $resolver);
            $binding = $factory->createTransport();
            $pending = null;
            $cancel = static function () use (&$pending): void {
                $pending->cancel();
            };
            if ($stage === 'dns') {
                $resolver->after = $cancel;
                $body = 'synthetic';
            } else {
                $body = new PreparationStream(Utils::streamFor('synthetic'), $cancel);
            }
            $before = $this->counters();
            $pending = $binding->client->sendAsync(
                new Request('POST', 'http://guard.test:8090/echo', [], $body)
            );
            $binding->driver->tick();
            self::assertSame(PromiseInterface::REJECTED, $pending->getState());
            self::assertSame(0, $binding->driver->counters()['active']);
            $this->assertNoContact($before, $this->counters());
        }
    }
    public function testObserveAndDisabledDelegateOriginalRequestAndOptions(): void
    {
        foreach (['observe', 'disabled'] as $mode) {
            $calls = [];
            $leaf = static function (
                RequestInterface $request,
                array $options
            ) use (&$calls) {
                $calls[] = [$request, $options];
                return Create::promiseFor(new Response(201));
            };
            $provider = new TransportStackProvider(
                [],
                ['verify' => false, 'timeout' => 0, 'idn_conversion' => true],
                $leaf
            );
            [$factory] = $this->fixture(['mode' => $mode], null, $provider);
            $binding = $factory->createTransport();
            $request = new Request(
                'POST',
                'http://10.23.4.12:8090/echo',
                ['X-Test' => 'synthetic'],
                'body'
            );
            $response = $binding->client->send(
                $request,
                [
                    'stream' => true,
                    'delay' => 7,
                    'curl' => [CURLOPT_FOLLOWLOCATION => true],
                    'opaque-user-option' => 'kept',
                    'allow_redirects' => false,
                ]
            );
            self::assertSame(201, $response->getStatusCode());
            self::assertCount(1, $calls);
            self::assertSame(
                $request->getUri()->getHost(),
                $calls[0][0]->getUri()->getHost()
            );
            self::assertSame('kept', $calls[0][1]['opaque-user-option']);
            self::assertTrue($calls[0][1]['stream']);
            self::assertSame(7, $calls[0][1]['delay']);
            self::assertTrue($calls[0][1]['curl'][CURLOPT_FOLLOWLOCATION]);
            self::assertTrue($calls[0][1]['idn_conversion']);
            self::assertSame(
                0,
                $binding->driver->counters()['nativeConstructed']
            );
        }
    }
    public function testMalformedPlanPinIsRejectedBeforeContact(): void
    {
        [$factory, , , $registry] = $this->fixture();
        $before = $this->counters();
        $target = (new TargetNormalizer())->normalize(
            new Request('GET', 'http://guard.test:8090/echo')
        );
        $context = $registry->newContext();
        $plan = new \Netresearch\HttpGuard\ConnectionPlan(
            $target,
            'GET',
            ['203.0.115.100,10.23.4.12'],
            null,
            $context->policyRevision,
            'synthetic',
            new \DateTimeImmutable('2026-10-08T00:00:00Z')
        );
        $pin = new \ReflectionMethod(
            \Netresearch\HttpGuard\Transport\TransferLease::class,
            'pin'
        );
        try {
            $pin->invoke(null, $plan);
            self::fail('Malformed pin accepted');
        } catch (PolicyException $error) {
            self::assertSame('resolution_unverified', $error->reasonCode());
        }
        $this->assertNoContact($before, $this->counters());
    }
    public function testHostHeaderMismatchAndBothMixedAddressFamiliesHaveZeroContact(): void
    {
        [$factory] = $this->fixture();
        foreach (['other.test:8090', 'guard.test:8092'] as $host) {
            $this->zeroContact(
                fn() => $factory
                    ->publicClient()
                    ->sendRequest(
                        new Request(
                            'GET',
                            'http://guard.test:8090/echo',
                            ['Host' => $host]
                        )
                    ),
                'authority_mismatch'
            );
        }
        foreach ([
            ['203.0.115.100', 'fd42::12'],
            ['2600:7e00:6775:6172::100', '10.23.4.12'],
        ] as $ips) {
            $resolver = new TransportResolver();
            $resolver->answers['guard.test'] = [$ips];
            [$factory] = $this->fixture([], $resolver);
            $binding = $factory->createTransport();
            $this->zeroContact(
                fn() => $binding->client->request(
                    'GET',
                    'http://guard.test:8090/echo',
                    [
                        'force_ip_resolve' => str_contains($ips[0], ':') ? 'v6' : 'v4',
                    ]
                ),
                'address_forbidden'
            );
            self::assertSame(
                0,
                $binding->driver->counters()['nativeConstructed']
            );
        }
    }
    public function testNativeIpv6MappedAndCompleteDualStackFallbackPins(): void
    {
        foreach ([
            ['2600:7e00:6775:6172::100'],
            ['2600:7e00:6775:6172::1', '2600:7e00:6775:6172::100'],
            ['203.0.115.1', '2600:7e00:6775:6172::100'],
            ['2600:7e00:6775:6172::1', '203.0.115.100'],
        ] as $addresses) {
            $resolver = new TransportResolver();
            $resolver->answers['guard.test'] = [$addresses, ['10.23.4.12']];
            [$factory] = $this->fixture([], $resolver);
            $binding = $factory->createTransport(
                ['connect_timeout' => 0.3, 'timeout' => 1]
            );
            $before = $this->counters();
            $response = $binding->client->request('GET', 'http://guard.test:8090/echo');
            $echo = json_decode(
                (string) $response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            self::assertSame('public-a', $echo['label']);
            self::assertSame('guard.test:8090', $echo['host']);
            self::assertSame(1, $resolver->calls);
            $after = $this->counters();
            self::assertSame(
                $before['public-a']['requests'] + 1,
                $after['public-a']['requests']
            );
            self::assertSame(
                $before['private']['tcp'],
                $after['private']['tcp']
            );
        }
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $response = $binding->client->request(
            'GET',
            'http://[::ffff:203.0.115.100]:8090/echo'
        );
        self::assertSame(
            'public-a',
            json_decode(
                (string) $response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            )['label']
        );
        self::assertSame(0, $binding->driver->counters()['active']);
    }
    public function testBodySecretsCannotFollow307Or308OrSchemeRelativeDifferentOrigin(): void
    {
        foreach ([307, 308] as $code) {
            foreach (['http://erp.test:8090/echo', '//other.test:8090/echo'] as $location) {
                $rewrite = static fn(callable $next) => static fn(
                    RequestInterface $request,
                    array $options
                ) => $next($request, $options)->then(
                    static fn(ResponseInterface $response) => $response
                        ->withStatus($code)
                        ->withHeader('Location', $location)
                );
                [$factory] = $this->fixture([], null, new TransportStackProvider([$rewrite]));
                $binding = $factory->createTransport();
                $before = $this->counters();
                try {
                    $binding->client->request(
                        'POST',
                        'http://guard.test:8090/echo',
                        [
                            'body' => 'synthetic-body-secret',
                            'headers' => [
                                'X-Custom-Authentication' => 'synthetic-header-secret',
                            ],
                        ]
                    );
                    self::fail('Cross-origin preserving redirect followed');
                } catch (PolicyException $error) {
                    self::assertSame('redirect_forbidden', $error->reasonCode());
                }
                $after = $this->counters();
                self::assertSame(
                    $before['public-a']['requests'] + 1,
                    $after['public-a']['requests']
                );
                self::assertSame(
                    $before['public-b']['tcp'],
                    $after['public-b']['tcp']
                );
                self::assertSame(
                    $before['private']['tcp'],
                    $after['private']['tcp']
                );
            }
        }
        [$factory] = $this->fixture();
        $before = $this->counters();
        try {
            $factory->createTransport()->client->request(
                'GET',
                'http://guard.test:8090/redirect-private-ip'
            );
            self::fail('Literal private redirect followed');
        } catch (PolicyException $error) {
            self::assertSame('redirect_forbidden', $error->reasonCode());
        }
        $after = $this->counters();
        self::assertSame(
            $before['public-a']['requests'] + 1,
            $after['public-a']['requests']
        );
        self::assertSame($before['private']['tcp'], $after['private']['tcp']);
    }
    public function testSharedNormativePrivateUnboundCase(): void
    {
        $data = json_decode(
            file_get_contents(
                dirname(__DIR__, 3) . '/Resources/Private/HttpGuard/data/security-corpus/endpoint-cases.json'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $case = null;
        foreach ($data['cases'] as $candidate) {
            if ($candidate['id'] === 'EP-PRIVATE-UNBOUND') {
                $case = $candidate;
                break;
            }
        }
        self::assertNotNull($case);
        $input = $case['input'];
        $host = (new Uri($input['origin']))->getHost();
        $resolver = new TransportResolver();
        $resolver->answers[$host] = [[$input['address']]];
        $config = [
            'endpoints' => [
                'corpus-erp' => [
                    'origin' => $input['origin'],
                    'allowedCidrs' => $input['allowed_cidrs'],
                    'methods' => [$input['method']],
                    'purpose' => 'Normative shared corpus',
                    'owner' => 'Test maintainers',
                ],
            ],
        ];
        [$factory] = $this->fixture($config, $resolver);
        $binding = $factory->createTransport();
        $this->zeroContact(
            fn() => $binding->client->request(
                $input['method'],
                $input['origin'] . '/synthetic'
            ),
            $case['expected']['reason']
        );
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
    }
    public function testOutsideCidrAndLiteralLoopbackGrantsAreExact(): void
    {
        $resolver = new TransportResolver();
        $resolver->answers['erp.test'] = [['10.23.4.13']];
        [$factory] = $this->fixture($this->endpoint(), $resolver);
        $binding = $factory->createTransport([], 'erp');
        $this->zeroContact(
            fn() => $binding->client->request('POST', 'http://erp.test:8090/echo'),
            'endpoint_mismatch'
        );
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
        foreach (['127.0.0.1', '::1'] as $ip) {
            $origin = 'http://' . (str_contains($ip, ':') ? '[' . $ip . ']' : $ip) . ':18090';
            $profile = [
                'origin' => $origin,
                'allowedCidrs' => [$ip . (str_contains($ip, ':') ? '/128' : '/32')],
                'allowLoopback' => true,
                'methods' => ['GET'],
                'purpose' => 'Synthetic loopback regression',
                'owner' => 'Test maintainers',
            ];
            [$factory] = $this->fixture(['endpoints' => ['loop' => $profile]]);
            $this->zeroContact(
                fn() => $factory
                    ->publicClient()
                    ->sendRequest(new Request('GET', $origin . '/echo')),
                'address_forbidden'
            );
            $binding = $factory->createTransport([], 'loop');
            $before = $this->counters();
            $response = $binding->client->request('GET', $origin . '/echo');
            self::assertSame(
                'loopback',
                json_decode(
                    (string) $response->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                )['label']
            );
            $after = $this->counters();
            self::assertSame(
                $before['loopback']['requests'] + 1,
                $after['loopback']['requests']
            );
            self::assertSame(0, $binding->driver->counters()['active']);
        }
    }
    public function testExternalProviderCannotPutProjectMiddlewareBeforeBoundary(): void
    {
        [$normal] = $this->fixture();
        $provider = new class implements ClientStackProviderInterface
        {
            public function create(
                BoundaryMiddleware $boundary,
                TerminalGuardMiddleware $terminal
            ): ClientStackConfiguration
            {
                $stack = HandlerStack::create();
                $stack->push(
                    Middleware::mapRequest(
                        static fn(
                            RequestInterface $request
                        ) => $request->withUri(
                            new Uri('http://other.test:8090/echo')
                        )
                    ),
                    'accidental-prefix-rewrite'
                );
                $stack->push($boundary, 'nr/http-guard-boundary');
                $stack->push($terminal, 'nr/http-guard-terminal');
                return new ClientStackConfiguration($stack);
            }
        };
        $factory = new GuardedClientFactory(
            (new \ReflectionProperty($normal, 'engine'))->getValue($normal),
            (new \ReflectionProperty($normal, 'config'))->getValue($normal),
            (new \ReflectionProperty($normal, 'registry'))->getValue($normal),
            $provider
        );
        $this->zeroContact(
            fn() => $factory->createTransport(),
            'configuration_invalid'
        );
    }
    public function testLargeResponseUsesCallerSinkWithoutGuardBodyCopy(): void
    {
        [$factory] = $this->fixture();
        $binding = $factory->createTransport();
        $sink = tmpfile();
        self::assertIsResource($sink);
        memory_reset_peak_usage();
        $start = memory_get_usage(true);
        try {
            $response = $binding->client->request(
                'GET',
                'http://guard.test:8090/large',
                ['sink' => $sink]
            );
            self::assertSame(4 * 1024 * 1024, $response->getBody()->getSize());
            self::assertLessThan(
                4 * 1024 * 1024,
                memory_get_peak_usage(true) - $start
            );
            self::assertSame(0, $binding->driver->counters()['active']);
        } finally {
            fclose($sink);
        }
    }
    public function testObserveUnsupportedTransportReportsUnverifiableAndDelegates(): void
    {
        $config = GuardConfig::fromArray(['mode' => 'observe']);
        $clock = new TransportClock();
        $registry = new PolicyRegistry($config, $clock);
        $reporter = new class implements \Netresearch\HttpGuard\DecisionReporterInterface
        {
            public array $events = [];
            public function report(
                \Netresearch\HttpGuard\DecisionEvent $event
            ): void
            {
                $this->events[] = $event;
            }
        };
        $resolver = new TransportResolver();
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            $reporter
        );
        $calls = 0;
        $provider = new TransportStackProvider(
            [],
            [],
            static function ($request, array $options) use (&$calls) {
                ++$calls;
                return Create::promiseFor(new Response(200));
            }
        );
        $factory = new GuardedClientFactory($engine, $config, $registry, $provider);
        $binding = $factory->createTransport();
        $response = $binding->client->request(
            'GET',
            'http://guard.test:8090/echo',
            ['stream' => true]
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $calls);
        self::assertSame(0, $binding->driver->counters()['nativeConstructed']);
        $events = array_filter(
            $reporter->events,
            static fn(
                $event
            ) => $event->decision === 'unverifiable' && $event->reasonCode === 'option_forbidden'
        );
        self::assertCount(1, $events);
    }
}
