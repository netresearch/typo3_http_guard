<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

final readonly class RequestPolicyContext
{
    public function __construct(
        public string $mode,
        public string $policyRevision,
        public ClientScope $clientScope,
        public ?EndpointGrant $endpointGrant
    )
    {
    }
}
