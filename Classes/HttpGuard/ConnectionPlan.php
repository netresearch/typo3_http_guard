<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

use DateTimeImmutable;

final readonly class ConnectionPlan
{
    /**
     * @param list<string> $addresses
     */
    public function __construct(
        public Target $target,
        public string $method,
        public array $addresses,
        public ?string $profileId,
        public string $policyRevision,
        public string $resolverGeneration,
        public DateTimeImmutable $issuedAt,
    ) {}
}
