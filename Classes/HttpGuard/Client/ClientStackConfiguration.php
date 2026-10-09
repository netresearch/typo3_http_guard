<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard\Client;

use GuzzleHttp\HandlerStack;
final readonly class ClientStackConfiguration
{
    /**
     * @param HandlerStack<covariant callable(\Psr\Http\Message\RequestInterface, array<array-key, mixed>): \GuzzleHttp\Promise\PromiseInterface> $stack
     * @param array<string,mixed> $defaultOptions
     * @param (\Closure(): void)|null $registryAssertion
     */
    public function __construct(
        public HandlerStack $stack,
        public array $defaultOptions = [],
        public ?\Closure $registryAssertion = null
    )
    {
    }
}
