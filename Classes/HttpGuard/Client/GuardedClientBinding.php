<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Client;

use Netresearch\HttpGuard\RequestPolicyContext;
use Netresearch\HttpGuard\Transport\TransferDriverInterface;

/** @internal Credential-free transport and its inseparable progress driver. */
final readonly class GuardedClientBinding
{
    public function __construct(
        public \GuzzleHttp\Client $client,
        public TransferDriverInterface $driver,
        public RequestPolicyContext $context,
    ) {}
}
