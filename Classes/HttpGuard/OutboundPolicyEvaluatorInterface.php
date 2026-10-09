<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

interface OutboundPolicyEvaluatorInterface
{
    public function evaluate(
        \Psr\Http\Message\RequestInterface $request,
        RequestPolicyContext $context
    ): PolicyDecision;
}
