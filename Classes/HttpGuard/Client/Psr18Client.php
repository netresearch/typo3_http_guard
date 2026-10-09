<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class Psr18Client implements ClientInterface
{
    public function __construct(private GuardedClientBinding $binding) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->binding->client->sendRequest($request);
    }
}
