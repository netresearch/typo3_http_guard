<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

interface EndpointClientFactoryInterface
{
    public function forEndpoint(string $configuredEndpointId): \Psr\Http\Client\ClientInterface;
}
