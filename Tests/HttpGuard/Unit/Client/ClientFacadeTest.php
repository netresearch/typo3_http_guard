<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Netresearch\HttpGuard\Client\GuardedClientBinding;
use Netresearch\HttpGuard\Client\Psr18Client;
use Netresearch\HttpGuard\Client\PublicFetchClient;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\Transport\TransferDriver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientFacadeTest extends TestCase
{
    #[DataProvider('publicMethods')]
    public function testPublicFetchCreatesTheSpecifiedCredentialFreeRequest(string $method): void
    {
        $history  = [];
        $response = new Response(200, [], 'fixture');
        $stack    = HandlerStack::create(new MockHandler([$response]));
        $stack->push(Middleware::history($history));
        $binding = $this->binding(new Client(['handler' => $stack]));
        self::assertSame(
            $response,
            (new PublicFetchClient($binding, 3))->fetch(new Uri('https://api.example/path'), $method),
        );
        self::assertCount(1, $history);
        $sent = $history[0]['request'];
        self::assertSame($method, $sent->getMethod());
        self::assertSame('https://api.example/path', (string) $sent->getUri());
        self::assertSame('*/*', $sent->getHeaderLine('Accept'));
        self::assertSame('gzip, deflate', $sent->getHeaderLine('Accept-Encoding'));
        self::assertSame('HTTP-Guard-PublicFetch/0.1', $sent->getHeaderLine('User-Agent'));
        self::assertFalse($sent->hasHeader('Authorization'));
        self::assertFalse($sent->hasHeader('Cookie'));
        self::assertFalse($history[0]['options']['http_errors']);
        foreach (['max' => 3, 'strict' => true, 'referer' => false, 'protocols' => ['http', 'https']] as $key => $expected) {
            self::assertSame($expected, $history[0]['options']['allow_redirects'][$key]);
        }
    }

    public function testUnsupportedPublicMethodIsRejectedBeforeTheMockHandler(): void
    {
        $mock    = new MockHandler([new Response(200)]);
        $binding = $this->binding(new Client(['handler' => HandlerStack::create($mock)]));
        try {
            (new PublicFetchClient($binding, 3))->fetch(new Uri('https://api.example'), 'POST');
            self::fail('Unsupported public method was accepted');
        } catch (PolicyException $exception) {
            self::assertSame('option_forbidden', $exception->reasonCode());
        }
        self::assertSame(1, $mock->count());
    }

    public function testPsr18ReturnsHttpErrorResponseAndDoesNotFollowARedirect(): void
    {
        $history  = [];
        $notFound = new Response(404);
        $redirect = new Response(302, ['Location' => 'https://other.example/']);
        $mock     = new MockHandler([$notFound, $redirect]);
        $stack    = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $client  = new Psr18Client($this->binding(new Client(['handler' => $stack])));
        $request = new Request('GET', 'https://api.example');
        self::assertSame($notFound, $client->sendRequest($request));
        self::assertSame($redirect, $client->sendRequest($request));
        self::assertCount(2, $history);
        self::assertSame(0, $mock->count());
        self::assertFalse($history[1]['options']['allow_redirects']);
        self::assertFalse($history[1]['options']['http_errors']);
    }

    /** @return iterable<string, array{string}> */
    public static function publicMethods(): iterable
    {
        yield 'get' => ['GET'];
        yield 'head' => ['HEAD'];
    }

    private function binding(Client $client): GuardedClientBinding
    {
        $context = (new PolicyRegistry(GuardConfig::fromArray([]), new SystemClock()))->newContext();

        return new GuardedClientBinding($client, new TransferDriver(), $context);
    }
}
