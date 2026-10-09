<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard\Transport;

final class InvocationToken
{
    private function __clone()
    {
    }
    public function __serialize(): array
    {
        throw new \LogicException('Guard context is not serializable');
    }
    /** @param array<string,mixed> $data */
    public function __unserialize(array $data): void
    {
        throw new \LogicException('Guard context is not serializable');
    }
}
