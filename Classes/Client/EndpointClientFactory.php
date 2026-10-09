<?php

declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Client;

use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\HttpGuard\EndpointClientFactoryInterface;
use Psr\Http\Client\ClientInterface;
final readonly class EndpointClientFactory implements EndpointClientFactoryInterface
{
    public function __construct(private GuardedClientFactory $factory)
    {
    }
    public function forEndpoint(string $configuredEndpointId): ClientInterface
    {
        return $this->factory->forEndpoint($configuredEndpointId);
    }
}
