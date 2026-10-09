<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Diagnostics;

final readonly class DiagnosticResult
{
    /** @param array<string, mixed> $payload */
    public function __construct(public array $payload, public int $exitCode)
    {
    }
}
