<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);

namespace Netresearch\NrVault\Tests\Functional\Http;

use Closure;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\ClockInterface;
use Netresearch\HttpGuard\DecisionEvent;
use Netresearch\HttpGuard\DecisionReporterInterface;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\StaticThenDnsResolver;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\HttpGuard\Transport\GuardedTransferFactory;
use Netresearch\HttpGuard\Transport\TerminalGuardMiddleware;
use Netresearch\HttpGuard\Transport\TransferDriver;
use Netresearch\HttpGuard\WireDnsQuery;
use Netresearch\NrVault\Audit\AuditLogServiceInterface;
use Netresearch\NrVault\Exception\RequestCancelledException;
use Netresearch\NrVault\Exception\VaultException;
use Netresearch\NrVault\Http\CancellationSignalInterface;
use Netresearch\NrVault\Http\Guard\VaultGuardAdapter;
use Netresearch\NrVault\Http\OAuth\OAuthConfig;
use Netresearch\NrVault\Http\SecretPlacement;
use Netresearch\NrVault\Http\SecureHttpClientFactory;
use Netresearch\NrVault\Http\VaultHttpClient;
use Netresearch\NrVault\Service\VaultServiceInterface;
use Netresearch\NrVault\Tests\Functional\Http\Fixtures\PinnedDnsResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;
use ReflectionProperty;
use RuntimeException;
use Throwable;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(VaultGuardAdapter::class)]
#[CoversClass(VaultHttpClient::class)]
#[AllowMockObjectsWithoutExpectations]
final class GuardAdapterTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['netresearch/nr-vault'];

    /** @var resource|null */
    private static $server;

    private static int $port;

    private static string $directory;

    /** @var list<DecisionEvent> */
    private array $events = [];

    /** @var list<array<mixed>> */
    private array $audit = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!class_exists(GuardedClientFactory::class)) {
            self::markTestSkipped(
                'Optional nr-http-guard extension source is not installed',
            );
        }

        $socket = stream_socket_server('tcp://127.0.0.1:0', $error, $message);
        if ($socket === false) {
            throw new RuntimeException('Cannot reserve fixture port', 8636426681);
        }

        $address = stream_socket_get_name($socket, false);
        if (!\is_string($address) || ($separator = strrpos($address, ':')) === false) {
            throw new RuntimeException('Cannot inspect fixture port', 8923269715);
        }

        self::$port = (int) substr($address, $separator + 1);
        fclose($socket);
        self::$directory = sys_get_temp_dir() . '/vault-guard-wire-' . bin2hex(random_bytes(8));
        mkdir(self::$directory, 0o700);
        $server = proc_open(
            [
                PHP_BINARY,
                '-S',
                '0.0.0.0:' . self::$port,
                __DIR__ . '/Fixtures/guard-router.php',
            ],
            [
                ['pipe', 'r'],
                ['file', self::$directory . '/server.log', 'a'],
                ['file', self::$directory . '/server.log', 'a'],
            ],
            $pipes,
            null,
            ['VAULT_GUARD_HITS' => self::$directory],
        );
        if (!\is_resource($server)) {
            throw new RuntimeException('Cannot start fixture server', 6200227403);
        }

        self::$server = $server;
        fclose($pipes[0]);
        $ready = false;
        // A refused connection is expected only while this owned fixture starts.
        set_error_handler(static fn (): bool => true);

        try {
            for ($attempt = 0; $attempt < 100; ++$attempt) {
                $probe = stream_socket_client(
                    'tcp://127.0.0.1:' . self::$port,
                    $error,
                    $message,
                    0.1,
                );
                if (\is_resource($probe)) {
                    fclose($probe);
                    $ready = true;
                    break;
                }

                usleep(20000);
            }
        } finally {
            restore_error_handler();
        }

        if (!$ready) {
            throw new RuntimeException('Fixture server did not become reachable', 6929036655);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (\is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }

        if (isset(self::$directory)) {
            foreach (self::fixtureFiles(self::$directory . '/*') as $file) {
                unlink($file);
            }

            rmdir(self::$directory);
        }

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->events = [];
        $this->audit = [];
        $this->setHttpConfiguration(
            [
                'timeout' => 20,
                'connect_timeout' => 2,
                'allow_redirects' => false,
            ],
        );
    }

    #[Test]
    public function enforcedResourceUsesItsPinnedProfileAndAuditsOneWireContact(): void
    {
        $factory = $this->factory('enforce', 'resource');
        $client = $this->client(
            $factory,
            ['fixture-resource-secret' => 'fixture-bearer-token'],
        );
        $response = $client
            ->withAuthentication('fixture-resource-secret', SecretPlacement::Bearer)
            ->sendRequest(
                new Request(
                    'GET',
                    $this->url('resource-guard.test', '/resource', 'resource'),
                ),
            );
        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $this->hits('resource'));
        self::assertTrue($this->hits('resource')[0]['authorization']);
        self::assertCount(1, $this->audit);
    }

    #[Test]
    public function methodRefusalAndPublicProfileCannotReadASecretOrContactWire(): void
    {
        foreach (['method' => 'resource', 'public' => null] as $case => $profile) {
            $client = $this->client($this->factory('enforce', $profile), []);

            try {
                $client
                    ->withAuthentication('fixture-never-read', SecretPlacement::Bearer)
                    ->sendRequest(
                        new Request(
                            'POST',
                            $this->url(
                                'resource-guard.test',
                                '/resource',
                                $case,
                            ),
                        ),
                    );
                self::fail('Policy refusal must precede secret retrieval');
            } catch (PolicyException) {
                self::assertSame([], $this->hits($case));
            }
        }
    }

    #[Test]
    public function oauthUsesSeparateTokenOriginAndKeepsCacheAcrossTimeoutClone(): void
    {
        $tokenFactory = $this->factory('enforce', 'token');
        $resourceFactory = $this->factory('enforce', 'resource', $tokenFactory);
        $client = $this
            ->client(
                $resourceFactory,
                [
                    'fixture-id' => 'fixture-client',
                    'fixture-secret' => 'fixture-client-secret',
                ],
            )
            ->withOAuth($this->oauth('oauth'));
        self::assertSame(
            200,
            $client
                ->sendRequest(
                    new Request(
                        'GET',
                        $this->url('resource-guard.test', '/resource', 'oauth'),
                    ),
                )
                ->getStatusCode(),
        );
        self::assertSame(
            200,
            $client
                ->withTimeout(9)
                ->withReason('fixture follow-up')
                ->sendRequest(
                    new Request(
                        'GET',
                        $this->url('resource-guard.test', '/resource', 'oauth'),
                    ),
                )
                ->getStatusCode(),
        );
        $hits = $this->hits('oauth');
        self::assertCount(3, $hits);
        self::assertCount(
            1,
            array_filter(
                $hits,
                static fn (
                    array $hit,
                ): bool => $hit['host'] === 'token-guard.test' && $hit['method'] === 'POST',
            ),
        );
        self::assertCount(
            2,
            array_filter(
                $hits,
                static fn (
                    array $hit,
                ): bool => $hit['host'] === 'resource-guard.test' && $hit['authorization'],
            ),
        );
        self::assertCount(3, $this->audit);
    }

    #[Test]
    public function resourceGrantAndObserveTokenFactoryCannotAuthorizeTokenCredentials(): void
    {
        foreach ([
            'wrong-profile' => $this->factory('enforce', 'resource'),
            'observe-token' => $this->factory('observe', 'token'),
        ] as $case => $tokenFactory) {
            $client = $this
                ->client($this->factory('enforce', 'resource', $tokenFactory), [])
                ->withOAuth($this->oauth($case));

            try {
                $client->sendRequest(
                    new Request(
                        'GET',
                        $this->url('resource-guard.test', '/resource', $case),
                    ),
                );
                self::fail('Separate enforced token profile is required');
            } catch (Throwable $error) {
                self::assertTrue(
                    $error instanceof PolicyException || $error instanceof VaultException,
                );
                self::assertSame([], $this->hits($case));
            }
        }
    }

    #[Test]
    public function observeKeepsLegacyAllowlistAndSsrfPinWhileReportingWouldDeny(): void
    {
        $this->setHttpConfiguration(
            ['allowed_hosts' => ['resource-guard.test']],
        );
        $client = $this->client(
            $this->factory('observe', 'resource'),
            ['fixture-resource-secret' => 'fixture-bearer-token'],
        );
        self::assertSame(
            200,
            $client
                ->withAuthentication('fixture-resource-secret', SecretPlacement::Bearer)
                ->sendRequest(
                    new Request(
                        'POST',
                        $this->url(
                            'resource-guard.test',
                            '/resource',
                            'observe',
                        ),
                    ),
                )
                ->getStatusCode(),
        );
        self::assertCount(1, $this->hits('observe'));
        self::assertGreaterThan(
            0,
            \count(
                array_filter(
                    $this->events,
                    static fn (
                        DecisionEvent $event,
                    ): bool => $event->decision === 'would_deny',
                ),
            ),
        );
    }

    #[Test]
    public function disabledStillRefusesLegacyPrivateHostAndAddsNoDiagnostics(): void
    {
        $factory = $this->factory('disabled', 'resource');
        $client = $this->client($factory, []);

        try {
            $client
                ->withAuthentication('fixture-never-read', SecretPlacement::Bearer)
                ->sendRequest(
                    new Request(
                        'GET',
                        $this->url(
                            'resource-guard.test',
                            '/resource',
                            'disabled-blocked',
                        ),
                    ),
                );
            self::fail('Original Vault private-address gate must remain active');
        } catch (Throwable $error) {
            self::assertInstanceOf(VaultException::class, $error);
            self::assertSame([], $this->hits('disabled-blocked'));
        }

        $this->setHttpConfiguration(
            ['allowed_hosts' => ['resource-guard.test']],
        );
        self::assertSame(
            200,
            $this
                ->client($factory, [])
                ->sendRequest(
                    new Request(
                        'GET',
                        $this->url(
                            'resource-guard.test',
                            '/resource',
                            'disabled-allowed',
                        ),
                    ),
                )
                ->getStatusCode(),
        );
        self::assertCount(1, $this->hits('disabled-allowed'));
        self::assertSame([], $this->events);
    }

    #[Test]
    public function observeOauthWithoutATokenBindingKeepsOriginalTokenChecks(): void
    {
        $this->setHttpConfiguration(
            ['allowed_hosts' => ['resource-guard.test', 'token-guard.test']],
        );
        $factory = $this->factory('observe', 'resource');
        self::assertNull($factory->guardTokenFactory()?->guardMode());
        $client = $this
            ->client(
                $factory,
                [
                    'fixture-id' => 'fixture-client',
                    'fixture-secret' => 'fixture-client-secret',
                ],
            )
            ->withOAuth($this->oauth('observe-oauth'));
        self::assertSame(
            200,
            $client
                ->sendRequest(
                    new Request(
                        'GET',
                        $this->url(
                            'resource-guard.test',
                            '/resource',
                            'observe-oauth',
                        ),
                    ),
                )
                ->getStatusCode(),
        );
        self::assertCount(2, $this->hits('observe-oauth'));
    }

    #[Test]
    public function enforcedActiveCancellationClosesTheMatchingWireTransfer(): void
    {
        $client = $this
            ->client(
                $this->factory('enforce', 'resource'),
                ['fixture-resource-secret' => 'fixture-bearer-token'],
            )
            ->withAuthentication('fixture-resource-secret', SecretPlacement::Bearer);
        self::assertTrue($client->supportsCancellation());
        $signal = new class (self::$directory) implements CancellationSignalInterface {
            public function __construct(private readonly string $directory) {}

            public function isCancelled(): bool
            {
                return \is_array($files = glob($this->directory . '/cancel-*.json')) && $files !== [];
            }
        };

        try {
            $client->sendCancellable(
                new Request(
                    'GET',
                    $this->url('resource-guard.test', '/chunks', 'cancel'),
                ),
                $signal,
            );
            self::fail('Cancellation must abort the active transfer');
        } catch (RequestCancelledException) {
            usleep(400000);
            $hits = $this->hits('cancel');
            self::assertCount(1, $hits);
            self::assertLessThan(30, $hits[0]['chunks']);
            self::assertCount(1, $this->audit);
        }
    }

    #[Test]
    public function enforcedStreamingCloseStopsTheMatchingTransfer(): void
    {
        $client = $this->client($this->factory('enforce', 'resource'), [])->withTimeout(8);
        self::assertTrue($client->supportsStreaming());
        $response = $client->sendStreaming(
            new Request(
                'GET',
                $this->url('resource-guard.test', '/chunks', 'stream'),
            ),
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString(
            'chunk',
            $response->getBody()->read(16),
        );
        $response->getBody()->close();
        usleep(400000);
        self::assertCount(1, $this->hits('stream'));
        self::assertLessThan(30, $this->hits('stream')[0]['chunks']);
    }

    #[Test]
    public function allAuthenticationPlacementsRetainWireSemanticsAndRedactedAudit(): void
    {
        $factory = $this->factory('enforce', 'resource');
        foreach ([
            'bearer' => [SecretPlacement::Bearer, 'fixture-value', [], 'authorization'],
            'basic' => [
                SecretPlacement::BasicAuth,
                'fixture-user:fixture-password',
                [],
                'authorization',
            ],
            'header' => [
                SecretPlacement::Header,
                'fixture-value',
                ['headerName' => 'X-Fixture-Key'],
                'custom_header',
            ],
            'apikey' => [SecretPlacement::ApiKey, 'fixture-value', [], 'api_key_header'],
            'query' => [SecretPlacement::QueryParam, 'fixture-value', [], 'query_secret'],
            'body' => [SecretPlacement::BodyField, 'fixture-value', [], 'body_secret'],
        ] as $case => [$placement, $secret, $options, $field]) {
            $client = $this
                ->client($factory, ['fixture-secret-ref' => $secret])
                ->withAuthentication('fixture-secret-ref', $placement, $options);
            $request = new Request(
                'GET',
                $this->url('resource-guard.test', '/resource', 'auth-' . $case),
                ['Content-Type' => 'application/json'],
                '{}',
            );
            self::assertSame(
                200,
                $client->sendRequest($request)->getStatusCode(),
            );
            $hits = $this->hits('auth-' . $case);
            self::assertCount(1, $hits);
            self::assertTrue($hits[0][$field]);
        }

        $auditText = json_encode($this->audit, JSON_THROW_ON_ERROR);
        foreach ([
            'fixture-value',
            'fixture-user:fixture-password',
            base64_encode('fixture-user:fixture-password'),
        ] as $value) {
            self::assertStringNotContainsString($value, $auditText);
        }

        self::assertCount(6, $this->audit);
    }

    #[Test]
    public function expiryAfterPreflightIsRecheckedBeforeWireContact(): void
    {
        $clock = new class () implements ClockInterface {
            public DateTimeImmutable $instant;

            public function __construct()
            {
                $this->instant = new DateTimeImmutable('2026-10-09T00:00:00Z');
            }

            public function now(): DateTimeImmutable
            {
                return $this->instant;
            }

            public function monotonic(): float
            {
                return hrtime(true) / 1000000000;
            }
        };
        $factory = $this->factory('enforce', 'resource', clock: $clock);
        $vault = $this->createMock(VaultServiceInterface::class);
        $vault
            ->expects(self::once())
            ->method('retrieve')
            ->willReturnCallback(
                static function () use ($clock): string {
                    $clock->instant = new DateTimeImmutable('2100-01-01T00:00:00Z');

                    return 'fixture-expiring-token';
                },
            );
        $audit = $this->createMock(AuditLogServiceInterface::class);
        $audit->expects(self::once())->method('log');
        $client = (new VaultHttpClient($vault, $audit, secureHttpClientFactory: $factory))->withAuthentication(
            'fixture-expiring-ref',
            SecretPlacement::Bearer,
        );

        try {
            $client->sendRequest(
                new Request(
                    'GET',
                    $this->url('resource-guard.test', '/resource', 'expired'),
                ),
            );
            self::fail('Terminal start must recheck expiry');
        } catch (PolicyException $error) {
            self::assertSame('grant_invalid', $error->reasonCode());
            self::assertSame([], $this->hits('expired'));
        }
    }

    #[Test]
    public function nonEnforceModesPreserveTheirLegacyStreamingTickerAndCancellation(): void
    {
        $this->setHttpConfiguration(
            ['allowed_hosts' => ['resource-guard.test']],
        );
        foreach (['observe', 'disabled'] as $mode) {
            $client = $this->client($this->factory($mode, 'resource'), []);
            $response = $client->sendStreaming(
                new Request(
                    'GET',
                    $this->url(
                        'resource-guard.test',
                        '/chunks',
                        $mode . '-stream',
                    ),
                ),
            );
            self::assertSame(200, $response->getStatusCode());
            self::assertStringContainsString(
                'chunk',
                $response->getBody()->read(16),
            );
            $response->getBody()->close();
            usleep(400000);
            self::assertCount(1, $this->hits($mode . '-stream'));
            self::assertLessThan(
                30,
                $this->hits($mode . '-stream')[0]['chunks'],
            );
            $signal = new class (
                self::$directory,
                $mode . '-cancel',
            ) implements CancellationSignalInterface {
                public function __construct(
                    private readonly string $directory,
                    private readonly string $case,
                ) {}

                public function isCancelled(): bool
                {
                    $files = glob($this->directory . '/' . $this->case . '-*.json');

                    return $files !== false && $files !== [];
                }
            };

            try {
                $client->sendCancellable(
                    new Request(
                        'GET',
                        $this->url(
                            'resource-guard.test',
                            '/chunks',
                            $mode . '-cancel',
                        ),
                    ),
                    $signal,
                );
                self::fail(
                    'Legacy ticker must drive the same cancellable client',
                );
            } catch (RequestCancelledException) {
                usleep(400000);
                self::assertCount(1, $this->hits($mode . '-cancel'));
                self::assertLessThan(
                    30,
                    $this->hits($mode . '-cancel')[0]['chunks'],
                );
            }
        }
    }

    #[Test]
    public function publicResourcePrivateTokenAndInverseStaySeparatelyBound(): void
    {
        $fixtureFile = __DIR__ . '/../../../.Build/http-guard-network.json';
        if (!is_file($fixtureFile)) {
            self::markTestSkipped(
                'Owned public/private HTTP Guard network fixture is not installed',
            );
        }

        $fixture = json_decode(
            (string) file_get_contents($fixtureFile),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($fixture);
        $publicIp = $fixture['publicIp'] ?? null;
        $privateIp = $fixture['privateIp'] ?? null;
        self::assertIsString($publicIp);
        self::assertIsString($privateIp);
        self::assertSame('203.0.115.150', $publicIp);
        self::assertSame('10.23.4.150', $privateIp);
        foreach ([$publicIp, $privateIp] as $ip) {
            $ready = false;
            set_error_handler(static fn (): bool => true);

            try {
                for ($attempt = 0; $attempt < 100; ++$attempt) {
                    $probe = stream_socket_client(
                        'tcp://' . $ip . ':' . self::$port,
                        $error,
                        $message,
                        0.1,
                    );
                    if (\is_resource($probe)) {
                        fclose($probe);
                        $ready = true;
                        break;
                    }

                    usleep(20000);
                }
            } finally {
                restore_error_handler();
            }

            self::assertTrue(
                $ready,
                'Owned network fixture interface must be reachable',
            );
        }

        foreach ([
            'public-resource' => [$publicIp, $privateIp, null, 'token'],
            'private-resource' => [$privateIp, $publicIp, 'resource', null],
        ] as $case => [$resourceIp, $tokenIp, $resourceProfile, $tokenProfile]) {
            $tokenFactory = $this->factory(
                'enforce',
                $tokenProfile,
                resourceIp: $resourceIp,
                tokenIp: $tokenIp,
            );
            $resourceFactory = $this->factory(
                'enforce',
                $resourceProfile,
                $tokenFactory,
                resourceIp: $resourceIp,
                tokenIp: $tokenIp,
            );
            $client = $this
                ->client(
                    $resourceFactory,
                    [
                        'fixture-id' => 'fixture-client',
                        'fixture-secret' => 'fixture-client-secret',
                    ],
                )
                ->withOAuth($this->oauth($case));
            foreach ([$client, $client->withTimeout(9)->withReason('fixture follow-up')] as $boundClient) {
                self::assertSame(
                    200,
                    $boundClient
                        ->sendRequest(
                            new Request(
                                'GET',
                                $this->url(
                                    'resource-guard.test',
                                    '/resource',
                                    $case,
                                ),
                            ),
                        )
                        ->getStatusCode(),
                );
            }

            $hits = $this->hits($case);
            self::assertCount(3, $hits);
            self::assertCount(
                1,
                array_filter(
                    $hits,
                    static fn (
                        array $hit,
                    ): bool => $hit['host'] === 'token-guard.test' && $hit['method'] === 'POST',
                ),
            );
            self::assertCount(
                2,
                array_filter(
                    $hits,
                    static fn (
                        array $hit,
                    ): bool => $hit['host'] === 'resource-guard.test' && $hit['authorization'],
                ),
            );
            $wrongClient = $this
                ->client($resourceFactory, [])
                ->withAuthentication('fixture-never-read', SecretPlacement::Bearer);

            try {
                $wrongClient->sendRequest(
                    new Request(
                        'POST',
                        $this->url(
                            'token-guard.test',
                            '/token',
                            $case . '-wrong-origin',
                        ),
                    ),
                );
                self::fail(
                    'Resource binding cannot transfer its grant to the token origin',
                );
            } catch (PolicyException) {
                self::assertSame([], $this->hits($case . '-wrong-origin'));
            }
        }
    }

    #[Test]
    public function preCancelledProtectedSendReadsNoSecretAndMakesNoContact(): void
    {
        $client = $this
            ->client($this->factory('enforce', 'resource'), [])
            ->withAuthentication('fixture-never-read', SecretPlacement::Bearer);
        $signal = new class () implements CancellationSignalInterface {
            public function isCancelled(): bool
            {
                return true;
            }
        };

        try {
            $client->sendCancellable(
                new Request(
                    'GET',
                    $this->url('resource-guard.test', '/resource', 'pre-cancel'),
                ),
                $signal,
            );
            self::fail('Pre-cancelled call must not reach the wire');
        } catch (RequestCancelledException) {
            self::assertSame([], $this->hits('pre-cancel'));
            self::assertCount(1, $this->audit);
        }
    }

    #[Test]
    public function truncatedProtectedStreamThrowsAndLeavesNoBackgroundTransfer(): void
    {
        $client = $this->client($this->factory('enforce', 'resource'), []);
        $body = null;
        $failure = null;

        try {
            $response = $client->sendStreaming(
                new Request(
                    'GET',
                    $this->url('resource-guard.test', '/short', 'truncated'),
                ),
            );
            $body = $response->getBody();
            $body->getContents();
        } catch (Throwable $error) {
            $failure = $error;
        } finally {
            if ($body instanceof StreamInterface) {
                $body->close();
            }
        }

        self::assertNotNull(
            $failure,
            'A truncated body must never be returned as a complete response',
        );
        self::assertCount(1, $this->hits('truncated'));
        self::assertCount(1, $this->audit);
        $after = $this->hits('truncated');
        usleep(200000);
        self::assertSame($after, $this->hits('truncated'));
    }

    #[Test]
    public function protectedOAuthTokenBindingWorksWithCancellationAndStreamingSends(): void
    {
        foreach (['cancellable', 'streaming'] as $case) {
            $tokenFactory = $this->factory('enforce', 'token');
            $client = $this
                ->client(
                    $this->factory('enforce', 'resource', $tokenFactory),
                    [
                        'fixture-id' => 'fixture-client',
                        'fixture-secret' => 'fixture-client-secret',
                    ],
                )
                ->withOAuth($this->oauth($case));
            $signal = new class () implements CancellationSignalInterface {
                public function isCancelled(): bool
                {
                    return false;
                }
            };
            $request = new Request(
                'GET',
                $this->url('resource-guard.test', '/resource', $case),
            );
            $response = $case === 'cancellable' ? $client->sendCancellable($request, $signal) : $client->sendStreaming($request, $signal);
            self::assertSame(200, $response->getStatusCode());
            $response->getBody()->getContents();
            $response->getBody()->close();
            $hits = $this->hits($case);
            self::assertCount(2, $hits);
            self::assertCount(
                1,
                array_filter(
                    $hits,
                    static fn (
                        array $hit,
                    ): bool => $hit['host'] === 'token-guard.test' && $hit['method'] === 'POST',
                ),
            );
            self::assertCount(
                1,
                array_filter(
                    $hits,
                    static fn (
                        array $hit,
                    ): bool => $hit['host'] === 'resource-guard.test' && $hit['authorization'],
                ),
            );
        }
    }

    #[Test]
    public function cancellationDuringProtectedTokenTransferClosesTokenSocketBeforeResourceContact(): void
    {
        $tokenFactory = $this->factory('enforce', 'token');
        $client = $this
            ->client(
                $this->factory('enforce', 'resource', $tokenFactory),
                [
                    'fixture-id' => 'fixture-client',
                    'fixture-secret' => 'fixture-client-secret',
                ],
            )
            ->withOAuth(
                new OAuthConfig(
                    tokenEndpoint: $this->url(
                        'token-guard.test',
                        '/token-chunks',
                        'token-cancel',
                    ),
                    clientIdSecret: 'fixture-id',
                    clientSecretSecret: 'fixture-secret',
                ),
            );
        $signal = new class (self::$directory) implements CancellationSignalInterface {
            public function __construct(private readonly string $directory) {}

            public function isCancelled(): bool
            {
                $files = glob($this->directory . '/token-cancel-*.json');

                return $files !== false && $files !== [];
            }
        };
        $failure = null;

        try {
            $client->sendCancellable(
                new Request(
                    'GET',
                    $this->url(
                        'resource-guard.test',
                        '/resource',
                        'resource-after-token-cancel',
                    ),
                ),
                $signal,
            );
        } catch (RequestCancelledException $error) {
            $failure = $error;
        }

        self::assertNotNull($failure);
        usleep(400000);
        $hits = $this->hits('token-cancel');
        self::assertCount(1, $hits);
        self::assertSame('token-guard.test', $hits[0]['host']);
        self::assertSame('POST', $hits[0]['method']);
        self::assertLessThan(30, $hits[0]['chunks']);
        self::assertSame([], $this->hits('resource-after-token-cancel'));
        self::assertCount(2, $this->audit);
    }

    #[Test]
    public function rawOAuthUriRefusalPrecedesPsrParsingAndSecretsAcrossModes(): void
    {
        foreach (['enforce', 'observe', 'disabled'] as $mode) {
            $this->setHttpConfiguration(
                ['allowed_hosts' => ['resource-guard.test', 'token-guard.test']],
            );
            foreach (['fragment' => '#', 'backslash' => '&noise=\\'] as $vector => $suffix) {
                $case = $mode . '-raw-' . $vector;
                $this->events = [];
                $tokenFactory = $this->factory($mode, 'token');
                $client = $this
                    ->client(
                        $this->factory($mode, 'resource', $tokenFactory),
                        $mode === 'enforce' ? [] : [
                            'fixture-id' => 'fixture-client',
                            'fixture-secret' => 'fixture-client-secret',
                        ],
                    )
                    ->withOAuth(
                        new OAuthConfig(
                            tokenEndpoint: $this->url('token-guard.test', '/token', $case) . $suffix,
                            clientIdSecret: 'fixture-id',
                            clientSecretSecret: 'fixture-secret',
                        ),
                    );
                $failure = null;

                try {
                    $response = $client->sendRequest(
                        new Request(
                            'GET',
                            $this->url(
                                'resource-guard.test',
                                '/resource',
                                $case,
                            ),
                        ),
                    );
                    self::assertSame(200, $response->getStatusCode());
                } catch (PolicyException $error) {
                    $failure = $error;
                }

                $rawDenials = array_filter(
                    $this->events,
                    static fn (
                        DecisionEvent $event,
                    ): bool => $event->reasonCode === 'invalid_target',
                );
                if ($mode === 'enforce') {
                    self::assertInstanceOf(PolicyException::class, $failure);
                    self::assertSame('invalid_target', $failure->reasonCode());
                    self::assertSame([], $this->hits($case));
                } else {
                    self::assertNull($failure);
                    self::assertCount(2, $this->hits($case));
                    if ($mode === 'observe') {
                        self::assertNotSame([], $rawDenials);
                        foreach ($rawDenials as $event) {
                            self::assertSame('would_deny', $event->decision);
                            self::assertNull($event->host);
                        }
                    } else {
                        self::assertSame([], $this->events);
                    }
                }
            }
        }
    }

    #[Test]
    public function sharedPrivateUnboundCorpusCannotBecomeAGrantThroughLegacyAllowedHosts(): void
    {
        $corpusPath = __DIR__ . '/../../../.Build/nr-http-guard-extension/Resources/Private/HttpGuard/data/security-corpus/endpoint-cases.json';
        $corpusHash = '4a61da4e50ee6efd5be31c5da97eb1f1eba3ed4bf6fc04376711d19417994825';
        self::assertSame($corpusHash, hash_file('sha256', $corpusPath));
        $raw = file_get_contents($corpusPath);
        self::assertIsString($raw);
        /** @var array{cases:list<array{id:string,input:array{address:string,origin:string,method:string,allowed_cidrs:list<string>,allow_loopback:bool,registry_issued_client_binding:bool,operator_denied_cidrs:list<string>},expected:array{decision:string,reason:string|null,no_network_assertion:bool}}>} $corpus */
        $corpus = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        $cases = array_values(
            array_filter(
                $corpus['cases'],
                static fn (
                    array $case,
                ): bool => $case['id'] === 'EP-PRIVATE-UNBOUND',
            ),
        );
        self::assertCount(1, $cases);
        $case = $cases[0];
        self::assertFalse($case['input']['registry_issued_client_binding']);
        self::assertTrue($case['expected']['no_network_assertion']);
        $host = parse_url($case['input']['origin'], PHP_URL_HOST);
        self::assertIsString($host);
        $this->setHttpConfiguration(['allowed_hosts' => [$host]]);
        $config = GuardConfig::fromArray(
            [
                'mode' => 'enforce',
                'endpoints' => [
                    'configured-but-unbound' => [
                        'origin' => $case['input']['origin'],
                        'allowedCidrs' => $case['input']['allowed_cidrs'],
                        'allowLoopback' => $case['input']['allow_loopback'],
                        'methods' => [$case['input']['method']],
                        'purpose' => 'Shared endpoint corpus',
                        'owner' => 'Vault regression',
                    ],
                ],
                'resolver' => ['staticHosts' => [$host => [$case['input']['address']]]],
                'deniedCidrs' => $case['input']['operator_denied_cidrs'],
            ],
        );
        $client = $this
            ->client($this->factory('enforce', null, config: $config), [])
            ->withAuthentication('unread-corpus-secret', SecretPlacement::Bearer);
        $driver = $this->actualGuardDriver($client);
        $before = $this->privateTargetCounters();
        $failure = null;

        try {
            $client->sendRequest(
                new Request(
                    $case['input']['method'],
                    $case['input']['origin'] . '/vault-unbound',
                ),
            );
        } catch (PolicyException $error) {
            $failure = $error;
        }

        self::assertInstanceOf(PolicyException::class, $failure);
        self::assertSame($case['expected']['reason'], $failure->reasonCode());
        $after = $this->privateTargetCounters();
        self::assertSame(
            $before,
            $after,
            'Denied Vault request must make no target TCP or HTTP contact',
        );
        self::assertSame(
            [
                'created' => 0,
                'released' => 0,
                'active' => 0,
                'peak' => 0,
                'nativeConstructed' => 0,
            ],
            $driver->counters(),
        );
        self::assertCount(1, $this->audit);
        $evidence = [
            'case_id' => $case['id'],
            'corpus_sha256' => $corpusHash,
            'legacy_allowed_hosts' => [$host],
            'bound_endpoint' => null,
            'reason' => $failure->reasonCode(),
            'driver' => $driver->counters(),
            'wire_before' => $before,
            'wire_after' => $after,
            'wire_delta' => [
                'tcp' => $after['tcp'] - $before['tcp'],
                'requests' => $after['requests'] - $before['requests'],
            ],
            'secret_retrieval_expectation' => 'never',
        ];
        self::assertNotFalse(
            file_put_contents(
                __DIR__ . '/../../../.Build/vault-guard-unbound-evidence.json',
                json_encode($evidence, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n",
            ),
        );
    }

    private function factory(
        string $mode,
        ?string $profile,
        ?SecureHttpClientFactory $tokenFactory = null,
        ?ClockInterface $clock = null,
        string $resourceIp = '127.0.0.1',
        string $tokenIp = '127.0.0.1',
        ?GuardConfig $config = null,
    ): SecureHttpClientFactory {
        $config ??= GuardConfig::fromArray(
            [
                'mode' => $mode,
                'endpoints' => [
                    'resource' => [
                        'origin' => 'http://resource-guard.test:' . self::$port,
                        'allowedCidrs' => [$resourceIp . '/32'],
                        'allowLoopback' => $resourceIp === '127.0.0.1',
                        'methods' => ['GET'],
                        'purpose' => 'Hermetic resource fixture',
                        'owner' => 'Vault regression',
                        'expiresAt' => '2099-01-01T00:00:00Z',
                    ],
                    'token' => [
                        'origin' => 'http://token-guard.test:' . self::$port,
                        'allowedCidrs' => [$tokenIp . '/32'],
                        'allowLoopback' => $tokenIp === '127.0.0.1',
                        'methods' => ['POST'],
                        'purpose' => 'Hermetic token fixture',
                        'owner' => 'Vault regression',
                    ],
                ],
                'resolver' => [
                    'staticHosts' => [
                        'resource-guard.test' => [$resourceIp],
                        'token-guard.test' => [$tokenIp],
                    ],
                ],
            ],
        );
        $clock ??= new SystemClock();
        $registry = new PolicyRegistry($config, $clock);
        $reporter = new class (
            function (DecisionEvent $event): void {
                $this->events[] = $event;
            },
        ) implements DecisionReporterInterface
        {
            /**
             * @param Closure(DecisionEvent): void $receiver
             */
            public function __construct(private readonly Closure $receiver) {}

            public function report(DecisionEvent $event): void
            {
                ($this->receiver)($event);
            }
        };
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            new StaticThenDnsResolver($config, new WireDnsQuery(), $clock),
            $registry,
            $clock,
            $reporter,
        );
        $guardFactory = new GuardedClientFactory($engine, $config, $registry);
        $legacyFactory = new SecureHttpClientFactory(new PinnedDnsResolver('127.0.0.1'));

        return new SecureHttpClientFactory(
            new PinnedDnsResolver('127.0.0.1'),
            new VaultGuardAdapter(
                $guardFactory,
                $engine,
                $profile,
                $tokenFactory,
                $legacyFactory,
            ),
        );
    }

    /**
     * @param array<string, string> $secrets
     */
    private function client(
        SecureHttpClientFactory $factory,
        array $secrets,
    ): VaultHttpClient {
        $vault = $this->createMock(VaultServiceInterface::class);
        if ($secrets === []) {
            $vault->expects(self::never())->method('retrieve');
        } else {
            $vault
                ->method('retrieve')
                ->willReturnCallback(
                    static function (
                        string $identifier,
                    ) use ($secrets): string {
                        if (!isset($secrets[$identifier])) {
                            throw new RuntimeException('Unexpected secret', 9231295702);
                        }

                        return $secrets[$identifier];
                    },
                );
        }

        $audit = $this->createMock(AuditLogServiceInterface::class);
        $audit
            ->method('log')
            ->willReturnCallback(
                function (...$arguments): int {
                    $this->audit[] = $arguments;

                    return 1;
                },
            );

        return new VaultHttpClient($vault, $audit, secureHttpClientFactory: $factory);
    }

    private function oauth(string $case): OAuthConfig
    {
        return new OAuthConfig(
            tokenEndpoint: $this->url('token-guard.test', '/token', $case),
            clientIdSecret: 'fixture-id',
            clientSecretSecret: 'fixture-secret',
        );
    }

    private function url(string $host, string $path, string $case): string
    {
        return 'http://' . $host . ':' . self::$port . $path . '?case=' . $case;
    }

    /**
     * @return list<array{method:string,host:string,path:string,authorization:bool,chunks:int,api_key_header:bool,custom_header:bool,query_secret:bool,body_secret:bool}>
     */
    private function hits(string $case): array
    {
        return array_map(
            static function (string $file): array {
                /** @var array{method:string,host:string,path:string,authorization:bool,chunks:int,api_key_header:bool,custom_header:bool,query_secret:bool,body_secret:bool} $record */
                $record = json_decode(
                    (string) file_get_contents($file),
                    true,
                    flags: JSON_THROW_ON_ERROR,
                );
                if (!\is_array($record)) {
                    throw new RuntimeException(
                        'Malformed fixture counter',
                        1786810101,
                    );
                }

                return $record;
            },
            self::fixtureFiles(self::$directory . '/' . $case . '-*.json'),
        );
    }

    /**
     * @param array<string,mixed> $values
     */
    private function setHttpConfiguration(array $values): void
    {
        $confVars = $GLOBALS['TYPO3_CONF_VARS'];
        self::assertIsArray($confVars);
        $httpConfig = $confVars['HTTP'] ?? [];
        self::assertIsArray($httpConfig);
        $confVars['HTTP'] = array_replace($httpConfig, $values);
        $GLOBALS['TYPO3_CONF_VARS'] = $confVars;
    }

    /**
     * @return list<string>
     */
    private static function fixtureFiles(string $pattern): array
    {
        $files = glob($pattern);
        if ($files === false) {
            throw new RuntimeException('Cannot inspect fixture counters', 6857416975);
        }

        return $files;
    }

    /**
     * @return array{tcp:int,requests:int}
     */
    private function privateTargetCounters(): array
    {
        $handle = curl_init('http://10.23.4.12:8091/stats');
        self::assertNotFalse($handle);
        self::assertTrue(
            curl_setopt_array(
                $handle,
                [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 3,
                    CURLOPT_PROXY => '',
                ],
            ),
        );

        try {
            $raw = curl_exec($handle);
            self::assertIsString(
                $raw,
                'Shared private fixture admin must be installed',
            );
            $values = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($values);
            self::assertArrayHasKey('tcp', $values);
            self::assertArrayHasKey('requests', $values);
            self::assertIsInt($values['tcp']);
            self::assertIsInt($values['requests']);

            return ['tcp' => $values['tcp'], 'requests' => $values['requests']];
        } finally {
            unset($handle);
        }
    }

    /**
     * Inspect the actual Vault inner client's terminal driver, without adding a production injection seam.
     */
    private function actualGuardDriver(
        VaultHttpClient $client,
    ): TransferDriver {
        $inner = (new ReflectionProperty(VaultHttpClient::class, 'innerClient'))->getValue(
            $client,
        );
        self::assertInstanceOf(Client::class, $inner);
        // @phpstan-ignore method.deprecated (G0 proves this concrete public API in both pinned Guzzle majors; G7 inherits an outdated interface removal annotation.)
        $stack = $inner->getConfig('handler');
        self::assertInstanceOf(HandlerStack::class, $stack);
        $entries = (new ReflectionProperty(HandlerStack::class, 'stack'))->getValue(
            $stack,
        );
        self::assertIsArray($entries);
        foreach ($entries as $entry) {
            self::assertIsArray($entry);
            if (($entry[0] ?? null) instanceof TerminalGuardMiddleware) {
                $transfers = (new ReflectionProperty(
                    TerminalGuardMiddleware::class,
                    'transfers',
                ))->getValue($entry[0]);
                self::assertInstanceOf(
                    GuardedTransferFactory::class,
                    $transfers,
                );
                $driver = (new ReflectionProperty(
                    GuardedTransferFactory::class,
                    'driver',
                ))->getValue($transfers);
                self::assertInstanceOf(
                    TransferDriver::class,
                    $driver,
                );

                return $driver;
            }
        }

        throw new RuntimeException(
            'Actual protected Vault driver is missing',
            8691641021,
        );
    }
}
