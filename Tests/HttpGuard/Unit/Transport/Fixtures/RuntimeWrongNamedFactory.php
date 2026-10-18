<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures;

/** Deliberately incompatible named input, with non-nullability preserved. */
interface RuntimeWrongNamedFactory
{
    public function create(string $request, array $options): \GuzzleHttp\Handler\EasyHandle;

    public function release(\GuzzleHttp\Handler\EasyHandle $easy): void;
}
