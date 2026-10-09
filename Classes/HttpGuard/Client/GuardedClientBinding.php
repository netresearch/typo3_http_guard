<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Client;

use GuzzleHttp\ClientInterface;
use Netresearch\HttpGuard\RequestPolicyContext;
use Netresearch\HttpGuard\Transport\TransferDriverInterface;
/** @internal Credential-free transport and its inseparable progress driver. */
final readonly class GuardedClientBinding
{
    public function __construct(
        public \GuzzleHttp\Client $client,
        public TransferDriverInterface $driver,
        public RequestPolicyContext $context
    )
    {
    }
}
