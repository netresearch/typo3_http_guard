<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
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
