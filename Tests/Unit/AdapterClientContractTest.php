<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\NrHttpGuard\Client\EndpointClientFactory;
use Netresearch\NrHttpGuard\Client\PublicFetchClient;
use Netresearch\NrHttpGuard\Http\CoreStackProvider;
use Netresearch\NrHttpGuard\Tests\Fixtures\AdapterRuntimeFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

final class AdapterClientContractTest extends TestCase
{
    private ?AdapterRuntimeFixture $fixture = null;

    protected function tearDown(): void
    {
        $this->fixture?->restore();
    }

    public function testEndpointAdapterProducesPsrClientAndKeepsScopeForOfflineDenials(): void
    {
        $this->fixture = new AdapterRuntimeFixture(['endpoints' => ['erp' => AdapterRuntimeFixture::endpoint()]]);
        $client        = (new EndpointClientFactory($this->fixture->factory))->forEndpoint('erp');
        self::assertInstanceOf(ClientInterface::class, $client);
        try {
            $client->sendRequest(new Request('GET', 'https://127.0.0.1/'));
            self::fail('Configured origin scope must survive adapter delegation');
        } catch (PolicyException $failure) {
            self::assertSame('endpoint_mismatch', $failure->reasonCode());
        }
    }

    public function testUnknownEndpointCannotCreateAuthority(): void
    {
        $this->fixture = new AdapterRuntimeFixture(['endpoints' => ['erp' => AdapterRuntimeFixture::endpoint()]]);
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('grant_invalid');
        (new EndpointClientFactory($this->fixture->factory))->forEndpoint('missing');
    }

    #[DataProvider('unsupportedMethods')]
    public function testPublicFetchAdapterKeepsRestrictedMethodsBeforeTransport(string $method): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('option_forbidden');
        (new PublicFetchClient($this->fixture->factory))->fetch(new Uri('https://127.0.0.1/'), $method);
    }

    /** @return iterable<string,array{string}> */
    public static function unsupportedMethods(): iterable
    {
        yield 'POST' => ['POST'];
        yield 'CONNECT' => ['CONNECT'];
        yield 'lowercase is not GET' => ['get'];
    }

    public function testPublicFetchAdapterCachesItsCompositionAndDelegatesGetAndHeadWithoutCredentials(): void
    {
        $this->fixture = new AdapterRuntimeFixture(
            ['mode' => 'disabled'],
            [
                'auth'    => ['private-user', 'private-password'],
                'headers' => ['Authorization' => 'Bearer private-token', 'Cookie' => 'private-cookie'],
                'query'   => ['private-query' => 'value'],
            ],
        );
        $client = (new GuzzleClientFactory())->getClient();
        self::assertInstanceOf(Client::class, $client);
        $stack = $client->getConfig('handler');
        self::assertInstanceOf(HandlerStack::class, $stack);
        $leaf = new MockHandler([new Response(203), new Response(204)]);
        $stack->setHandler($leaf);
        $core = $this->createMock(GuzzleClientFactory::class);
        $core->expects(self::once())->method('getClient')->willReturn($client);
        $factory = $this->fixture->library->contextFactory(
            $this->fixture->engine,
            $this->fixture->config,
            $this->fixture->policy,
            new CoreStackProvider($core, $this->fixture->middleware),
        );
        $fetch = new PublicFetchClient($factory);
        self::assertSame(203, $fetch->fetch(new Uri('https://8.8.8.8/get'))->getStatusCode());
        $request = $leaf->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://8.8.8.8/get', (string) $request->getUri());
        self::assertFalse($request->hasHeader('Authorization'));
        self::assertFalse($request->hasHeader('Cookie'));
        self::assertSame('HTTP-Guard-PublicFetch/0.1', $request->getHeaderLine('User-Agent'));
        self::assertSame(204, $fetch->fetch(new Uri('https://8.8.8.8/head'), 'HEAD')->getStatusCode());
        self::assertSame('HEAD', $leaf->getLastRequest()?->getMethod());
        self::assertSame(0, count($leaf));
    }
}
