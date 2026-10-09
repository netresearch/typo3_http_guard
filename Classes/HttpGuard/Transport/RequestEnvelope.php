<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard\Transport;

use GuzzleHttp\HandlerStack;
final readonly class RequestEnvelope
{
    public function __construct(
        public string $origin,
        public string $scheme,
        public ?HandlerStack $expectedHandler,
        public bool $publicFetch = false
    )
    {
    }
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
