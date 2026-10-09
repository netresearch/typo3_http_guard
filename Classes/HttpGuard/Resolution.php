<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard;

final readonly class Resolution
{
    /**
     * @param list<string> $addresses
     * @param list<string> $cnameChain
     */
    public function __construct(
        public array $addresses,
        public string $source,
        public ?int $ttlSeconds,
        public string $generation,
        public array $cnameChain = []
    )
    {
    }
}
