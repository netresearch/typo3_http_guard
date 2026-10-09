<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard;

final readonly class DecisionEvent
{
    public function __construct(
        public int $version,
        public string $time,
        public string $mode,
        public string $decision,
        public ?string $reasonCode,
        public ?string $profileId,
        public string $policyRevision,
        public ?string $addressClass,
        public ?string $scheme,
        public ?int $port,
        public ?string $resolverSource,
        public string $correlationId,
        public ?string $host = null
    )
    {
    }
}
