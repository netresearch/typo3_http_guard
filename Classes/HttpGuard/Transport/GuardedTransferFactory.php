<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Transport;

use GuzzleHttp\Promise\PromiseInterface;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\RequestPolicyContext;
use Psr\Http\Message\RequestInterface;

final readonly class GuardedTransferFactory
{
    public function __construct(
        private PolicyEngine $engine,
        private RequestPolicyContext $context,
        private TransferDriver $driver,
    ) {}

    /** @param array<string,mixed> $leafOptions */
    public function send(RequestInterface $request, array $leafOptions): PromiseInterface
    {
        return (new TransferLease($this->driver, $this->engine, $this->context, $request, $leafOptions))->promise();
    }
}
