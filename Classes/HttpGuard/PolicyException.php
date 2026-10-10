<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

use InvalidArgumentException;
use RuntimeException;

final class PolicyException extends RuntimeException implements OutboundPolicyExceptionInterface
{
    public const REASONS = [
        'invalid_target',
        'scheme_forbidden',
        'authority_mismatch',
        'address_forbidden',
        'resolution_unverified',
        'resolution_limit',
        'endpoint_mismatch',
        'grant_invalid',
        'transport_unsupported',
        'proxy_unsupported',
        'option_forbidden',
        'redirect_forbidden',
        'configuration_invalid',
    ];

    public function __construct(private readonly string $reason)
    {
        if (!in_array($reason, self::REASONS, true)) {
            throw new InvalidArgumentException('Unknown policy reason');
        }
        parent::__construct('Outbound HTTP policy: ' . $reason);
    }

    public function reasonCode(): string
    {
        return $this->reason;
    }
}
