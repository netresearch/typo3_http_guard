<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);

namespace Netresearch\NrVault\Tests\Unit\Http\Guard;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Netresearch\NrVault\Audit\AuditLogServiceInterface;
use Netresearch\NrVault\Exception\VaultException;
use Netresearch\NrVault\Http\Guard\VaultGuardAdapterInterface;
use Netresearch\NrVault\Http\OAuth\OAuthConfig;
use Netresearch\NrVault\Http\OAuth\OAuthTokenManager;
use Netresearch\NrVault\Http\SecretPlacement;
use Netresearch\NrVault\Http\SecureHttpClientFactory;
use Netresearch\NrVault\Http\VaultHttpClient;
use Netresearch\NrVault\Service\VaultServiceInterface;
use Netresearch\NrVault\Tests\Unit\Fixtures\AlwaysPublicDnsResolver;
use Netresearch\NrVault\Tests\Unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

#[CoversClass(VaultHttpClient::class)]
#[CoversClass(OAuthTokenManager::class)]
#[AllowMockObjectsWithoutExpectations]
final class VaultHttpClientGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = ['timeout' => 40, 'connect_timeout' => 7];
    }

    #[Test]
    public function fullRequestRefusalHappensBeforeResourceSecretRetrieval(): void
    {
        $vault = $this->createMock(VaultServiceInterface::class);
        $vault->expects(self::never())->method('retrieve');
        $inner = $this->createMock(ClientInterface::class);
        $inner->expects(self::never())->method('sendRequest');
        $adapter = $this->enforcedAdapter();
        $adapter->method('create')->willReturn($inner);
        $adapter->method('isHostAllowed')->willReturn(true);
        $adapter
            ->expects(self::once())
            ->method('assertRequestAllowed')
            ->willThrowException(new RuntimeException('Synthetic endpoint method denied'));
        $audit = $this->createMock(AuditLogServiceInterface::class);
        $audit->expects(self::once())->method('log');
        $factory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver(), $adapter);
        $client = (new VaultHttpClient($vault, $audit, secureHttpClientFactory: $factory))->withAuthentication(
            'synthetic-resource-secret',
            SecretPlacement::Bearer,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageToContain(
            'Synthetic endpoint method denied',
        );
        $client->sendRequest(
            new Request('POST', 'https://resource.internal.example/orders'),
        );
    }

    #[Test]
    public function oauthUsesSeparateFactoryAndRetainsTokenCacheAcrossTimeoutClone(): void
    {
        $vault = $this->createMock(VaultServiceInterface::class);
        $vault
            ->expects(self::exactly(2))
            ->method('retrieve')
            ->willReturnMap(
                [
                    ['synthetic-client-id', 'fixture-client'],
                    ['synthetic-client-secret', 'fixture-client-secret'],
                ],
            );
        $audit = $this->createMock(AuditLogServiceInterface::class);
        $audit->expects(self::exactly(3))->method('log');
        $resourceClient = $this->createMock(ClientInterface::class);
        $resourceClient
            ->expects(self::exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(
                static function (RequestInterface $request): Response {
                    self::assertSame(
                        'resource.internal.example',
                        $request->getUri()->getHost(),
                    );
                    self::assertSame(
                        'Bearer fixture-access-token',
                        $request->getHeaderLine('Authorization'),
                    );

                    return new Response(200);
                },
            );
        $tokenClient = $this->createMock(ClientInterface::class);
        $tokenClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturnCallback(
                static function (RequestInterface $request): Response {
                    self::assertSame(
                        'token.internal.example',
                        $request->getUri()->getHost(),
                    );
                    self::assertSame('POST', $request->getMethod());
                    self::assertFalse($request->hasHeader('Authorization'));
                    self::assertStringContainsString(
                        'client_secret=fixture-client-secret',
                        (string) $request->getBody(),
                    );

                    return new Response(
                        200,
                        [],
                        '{"access_token":"fixture-access-token","token_type":"Bearer","expires_in":3600}',
                    );
                },
            );
        $tokenAdapter = $this->enforcedAdapter();
        $tokenAdapter
            ->expects(self::once())
            ->method('create')
            ->willReturnCallback(
                static function (
                    array $options,
                ) use ($tokenClient): ClientInterface {
                    self::assertSame(40, $options['timeout']);

                    return $tokenClient;
                },
            );
        $tokenAdapter->method('isHostAllowed')->willReturn(true);
        $tokenAdapter
            ->expects(self::once())
            ->method('assertRequestAllowed')
            ->with(
                self::callback(
                    static fn (
                        RequestInterface $request,
                    ): bool => $request->getUri()->getHost() === 'token.internal.example' && $request->getMethod() === 'POST',
                ),
            );
        $tokenFactory = new SecureHttpClientFactory(
            new AlwaysPublicDnsResolver(),
            $tokenAdapter,
        );
        $resourceAdapter = $this->enforcedAdapter();
        $resourceAdapter->method('create')->willReturn($resourceClient);
        $resourceAdapter->method('isHostAllowed')->willReturn(true);
        $resourceAdapter->method('tokenFactory')->willReturn($tokenFactory);
        $resourceFactory = new SecureHttpClientFactory(
            new AlwaysPublicDnsResolver(),
            $resourceAdapter,
        );
        $base = new VaultHttpClient(
            $vault,
            $audit,
            secureHttpClientFactory: $resourceFactory,
        );
        $client = $base->withOAuth(
            OAuthConfig::clientCredentials(
                'https://token.internal.example/token',
                'synthetic-client-id',
                'synthetic-client-secret',
            ),
        );

        self::assertSame(
            200,
            $client
                ->sendRequest(new Request('POST', 'https://resource.internal.example/orders'))
                ->getStatusCode(),
        );
        self::assertSame(
            200,
            $client
                ->withReason('Synthetic cache check')
                ->withTimeout(9)
                ->sendRequest(new Request('POST', 'https://resource.internal.example/orders'))
                ->getStatusCode(),
        );
    }

    #[Test]
    public function absentSeparateTokenFactoryFailsBeforeClientCredentialsAreRead(): void
    {
        $vault = $this->createMock(VaultServiceInterface::class);
        $vault->expects(self::never())->method('retrieve');
        $adapter = $this->enforcedAdapter();
        $adapter
            ->method('create')
            ->willReturn($this->createMock(ClientInterface::class));
        $adapter->method('isHostAllowed')->willReturn(true);
        $adapter->method('tokenFactory')->willReturn(null);
        $factory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver(), $adapter);
        $client = (new VaultHttpClient(
            $vault,
            $this->createMock(AuditLogServiceInterface::class),
            secureHttpClientFactory: $factory,
        ))->withOAuth(
            OAuthConfig::clientCredentials(
                'https://token.internal.example/token',
                'synthetic-client-id',
                'synthetic-client-secret',
            ),
        );

        $this->expectException(VaultException::class);
        $this->expectExceptionMessageToContain(
            'separate protected token factory',
        );
        $client->sendRequest(
            new Request('POST', 'https://resource.internal.example/orders'),
        );
    }

    #[Test]
    public function protectedClientCannotAcceptAnArbitraryInjectedInnerClient(): void
    {
        $adapter = $this->enforcedAdapter();
        $factory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver(), $adapter);

        $this->expectException(VaultException::class);
        $this->expectExceptionMessageToContain('unverified inner client');
        new VaultHttpClient(
            $this->createMock(VaultServiceInterface::class),
            $this->createMock(AuditLogServiceInterface::class),
            $this->createMock(ClientInterface::class),
            secureHttpClientFactory: $factory,
        );
    }

    #[Test]
    public function protectedClientCannotInheritAnUnverifiedOauthManager(): void
    {
        $vault = $this->createMock(VaultServiceInterface::class);
        $legacyFactory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver());
        $legacyManager = new OAuthTokenManager(
            $vault,
            $this->createMock(ClientInterface::class),
            $legacyFactory,
        );
        $adapter = $this->enforcedAdapter();
        $adapter
            ->method('create')
            ->willReturn($this->createMock(ClientInterface::class));
        $factory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver(), $adapter);

        $this->expectException(VaultException::class);
        $this->expectExceptionMessageToContain('unverified OAuth manager');
        new VaultHttpClient(
            $vault,
            $this->createMock(AuditLogServiceInterface::class),
            oauthManager: $legacyManager,
            secureHttpClientFactory: $factory,
        );
    }

    private function enforcedAdapter(): VaultGuardAdapterInterface&MockObject
    {
        $adapter = $this->createMock(VaultGuardAdapterInterface::class);
        $adapter->method('mode')->willReturn('enforce');
        $adapter->method('validateRawUri')->willReturn(true);

        return $adapter;
    }
}
