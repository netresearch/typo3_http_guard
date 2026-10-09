<?php

declare (strict_types=1);
namespace Vendor\SitePackage\Service;

use GuzzleHttp\Psr7\Uri;
use Netresearch\HttpGuard\PublicFetchClientInterface;
use Netresearch\HttpGuard\TargetNormalizer;
use Psr\Http\Message\ResponseInterface;
final readonly class PublicDocument
{
    public function __construct(private PublicFetchClientInterface $client)
    {
    }
    public function fetch(string $rawUrl): ResponseInterface
    {
        (new TargetNormalizer())->assertRawUri($rawUrl);
        return $this->client->fetch(new Uri($rawUrl));
    }
}
