<?php

declare (strict_types=1);
namespace Vendor\SitePackage\Service;

use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\EndpointClientFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
final readonly class ErpClient
{
    private ClientInterface $client;
    public function __construct(EndpointClientFactoryInterface $factory)
    {
        $this->client = $factory->forEndpoint('erp-orders');
    }
    public function sendOrder(array $order): ResponseInterface
    {
        return $this->client->sendRequest(
            new Request(
                'POST',
                'https://erp.internal.example:8443/orders',
                ['Content-Type' => 'application/json'],
                json_encode($order, JSON_THROW_ON_ERROR)
            )
        );
    }
}
