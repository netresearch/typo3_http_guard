<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Client;

use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\TerminalGuardMiddleware;
interface ClientStackProviderInterface
{
    public function create(
        BoundaryMiddleware $boundary,
        TerminalGuardMiddleware $terminal
    ): ClientStackConfiguration;
}
