<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard;

final readonly class Target
{
    public function __construct(
        public \Psr\Http\Message\RequestInterface $canonicalRequest,
        public string $scheme,
        public string $host,
        public int $port,
        public string $origin,
        public ?string $literalIp,
    ) {}
}
