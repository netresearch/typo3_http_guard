<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard;

interface PublicFetchClientInterface
{
    public function fetch(
        \Psr\Http\Message\UriInterface $uri,
        string $method = 'GET',
    ): \Psr\Http\Message\ResponseInterface;
}
