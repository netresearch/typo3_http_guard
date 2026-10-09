<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;

    public function monotonic(): float;
}
