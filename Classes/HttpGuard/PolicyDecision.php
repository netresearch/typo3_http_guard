<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

final readonly class PolicyDecision
{
    public function __construct(
        public string $mode,
        public string $decision,
        public ?string $reasonCode,
        public ?string $profileId,
        public string $policyRevision,
    ) {}
}
