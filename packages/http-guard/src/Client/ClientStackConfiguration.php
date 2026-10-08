<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Client;

use GuzzleHttp\HandlerStack;
final readonly class ClientStackConfiguration
{
    /** @param array<string,mixed> $defaultOptions */
    public function __construct(
        public HandlerStack $stack,
        public array $defaultOptions = [],
        public ?\Closure $registryAssertion = null
    )
    {
    }
}
