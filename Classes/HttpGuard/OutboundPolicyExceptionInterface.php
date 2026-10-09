<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard;

interface OutboundPolicyExceptionInterface extends \Psr\Http\Client\ClientExceptionInterface
{
    public function reasonCode(): string;
}
