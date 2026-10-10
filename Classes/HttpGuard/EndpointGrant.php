<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

/** @internal Exact objects acquire meaning only through the issuing registry. */
final readonly class EndpointGrant
{
    private function __construct() {}

    private function __clone() {}

    public function __serialize(): array
    {
        throw new PolicyException('grant_invalid');
    }

    /**
     * @param array<array-key,mixed> $data
     */
    public function __unserialize(array $data): void
    {
        throw new PolicyException('grant_invalid');
    }
}
