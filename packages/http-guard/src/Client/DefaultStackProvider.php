<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Client;

use GuzzleHttp\HandlerStack;
use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\TerminalGuardMiddleware;
final class DefaultStackProvider implements ClientStackProviderInterface
{
    public function create(
        BoundaryMiddleware $boundary,
        TerminalGuardMiddleware $terminal
    ): ClientStackConfiguration
    {
        $stack = HandlerStack::create();
        $stack->push($boundary, 'nr/http-guard-boundary');
        $stack->push($terminal, 'nr/http-guard-terminal');
        return new ClientStackConfiguration($stack);
    }
}
