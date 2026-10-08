<?php

declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Client;

use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\PublicFetchClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
final class PublicFetchClient implements PublicFetchClientInterface
{
    private ?PublicFetchClientInterface $client = null;
    public function __construct(private readonly GuardedClientFactory $factory)
    {
    }
    public function fetch(
        UriInterface $uri,
        string $method = 'GET'
    ): ResponseInterface
    {
        $this->client ??= $this->factory->publicFetch();
        return $this->client->fetch($uri, $method);
    }
}
