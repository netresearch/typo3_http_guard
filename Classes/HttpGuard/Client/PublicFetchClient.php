<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard\Client;

use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PublicFetchClientInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
final readonly class PublicFetchClient implements PublicFetchClientInterface
{
    public function __construct(
        private GuardedClientBinding $binding,
        private int $maxRedirects
    )
    {
    }
    public function fetch(
        UriInterface $uri,
        string $method = 'GET'
    ): ResponseInterface
    {
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            throw new PolicyException('option_forbidden');
        }
        $request = new Request(
            $method,
            $uri,
            [
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip, deflate',
                'User-Agent' => 'HTTP-Guard-PublicFetch/0.1',
            ]
        );
        return $this->binding->client->send(
            $request,
            [
                'http_errors' => false,
                'allow_redirects' => [
                    'max' => $this->maxRedirects,
                    'strict' => true,
                    'referer' => false,
                    'protocols' => ['http', 'https'],
                ],
            ]
        );
    }
}
