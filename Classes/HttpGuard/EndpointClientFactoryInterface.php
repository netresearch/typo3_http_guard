<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard;

interface EndpointClientFactoryInterface
{
    public function forEndpoint(string $configuredEndpointId): \Psr\Http\Client\ClientInterface;
}
