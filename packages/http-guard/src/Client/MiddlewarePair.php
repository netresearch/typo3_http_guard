<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Client;

use Netresearch\HttpGuard\RequestPolicyContext;
use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\TerminalGuardMiddleware;
use Netresearch\HttpGuard\Transport\TransferDriverInterface;
final readonly class MiddlewarePair
{
    public function __construct(
        public BoundaryMiddleware $boundary,
        public TerminalGuardMiddleware $terminal,
        public TransferDriverInterface $driver,
        public RequestPolicyContext $context
    )
    {
    }
}
