<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);

namespace Netresearch\NrVault\Tests\Unit\Http\Guard;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Psr7\Request;
use Netresearch\NrVault\Http\CancellableTransport;
use Netresearch\NrVault\Http\Guard\VaultGuardAdapterInterface;
use Netresearch\NrVault\Http\SecureHttpClientFactory;
use Netresearch\NrVault\Http\TransportTickerInterface;
use Netresearch\NrVault\Tests\Unit\Fixtures\AlwaysPublicDnsResolver;
use Netresearch\NrVault\Tests\Unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

#[CoversClass(SecureHttpClientFactory::class)]
#[AllowMockObjectsWithoutExpectations]
final class SecureHttpClientFactoryGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = ['timeout' => 40, 'connect_timeout' => 7, 'allow_redirects' => false];
    }

    #[Test]
    public function explicitAdapterReceivesTheSameHardenedPlatformDefaults(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $adapter = $this->createMock(VaultGuardAdapterInterface::class);
        $adapter
            ->expects(self::once())
            ->method('create')
            ->willReturnCallback(
                static function (
                    array $options,
                ) use ($client): ClientInterface {
                    self::assertSame(12, $options['timeout']);
                    self::assertSame(7, $options['connect_timeout']);
                    self::assertFalse($options['debug']);
                    self::assertFalse($options['http_errors']);
                    self::assertFalse($options['allow_redirects']);

                    return $client;
                },
            );

        $factory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver(), $adapter);

        self::assertSame($client, $factory->create(12));
    }

    #[Test]
    public function cancellableBindingKeepsItsClientTickerAndPlatformBudgetsTogether(): void
    {
        $transport = new CancellableTransport(
            $this->createMock(GuzzleClientInterface::class),
            $this->createMock(TransportTickerInterface::class),
            24.0,
        );
        $adapter = $this->createMock(VaultGuardAdapterInterface::class);
        $adapter
            ->expects(self::once())
            ->method('createCancellable')
            ->with(
                self::callback(
                    static fn (
                        array $options,
                    ): bool => $options['timeout'] === 12,
                ),
                24.0,
                null,
            )
            ->willReturn($transport);

        $factory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver(), $adapter);

        self::assertSame($transport, $factory->createCancellable(12));
    }

    #[Test]
    public function protectedCapabilityFailureCannotReturnTheLegacyFallback(): void
    {
        $failure = new RuntimeException('Synthetic controlled transport unavailable');
        $adapter = $this->createMock(VaultGuardAdapterInterface::class);
        $adapter
            ->expects(self::once())
            ->method('createCancellable')
            ->willThrowException($failure);
        $factory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver(), $adapter);

        try {
            $factory->createCancellable();
            self::fail(
                'The protected branch must propagate its capability failure',
            );
        } catch (RuntimeException $actual) {
            self::assertSame($failure, $actual);
        }
    }

    #[Test]
    public function hostOnlyAndFullRequestPreflightsDelegateWithoutInventingAMethod(): void
    {
        $request = new Request('POST', 'https://erp.internal.example:8443/orders');
        $adapter = $this->createMock(VaultGuardAdapterInterface::class);
        $adapter
            ->expects(self::once())
            ->method('isHostAllowed')
            ->with('erp.internal.example')
            ->willReturn(true);
        $adapter
            ->expects(self::once())
            ->method('assertRequestAllowed')
            ->with($request);
        $adapter
            ->expects(self::once())
            ->method('validateRawUri')
            ->with('https://erp.internal.example:8443/orders#')
            ->willReturn(false);
        $factory = new SecureHttpClientFactory(new AlwaysPublicDnsResolver(), $adapter);

        self::assertTrue($factory->isHostAllowed('erp.internal.example'));
        $factory->assertRequestAllowed($request);
        self::assertFalse(
            $factory->guardValidateRawUri(
                'https://erp.internal.example:8443/orders#',
            ),
        );
    }
}
