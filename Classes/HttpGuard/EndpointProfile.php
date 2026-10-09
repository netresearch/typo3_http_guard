<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard;

final readonly class EndpointProfile
{
    /**
     * @param list<string> $allowedCidrs
     * @param list<string> $methods
     */
    public function __construct(
        public string $id,
        public string $origin,
        public array $allowedCidrs,
        public array $methods,
        public string $redirects,
        public bool $allowLoopback,
        public string $purpose,
        public string $owner,
        public ?\DateTimeImmutable $reviewAfter,
        public ?\DateTimeImmutable $expiresAt
    )
    {
    }
}
