<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

interface DnsQueryInterface
{
    public function query(string $absoluteFqdn, int $qtype): DnsAnswer;
}
