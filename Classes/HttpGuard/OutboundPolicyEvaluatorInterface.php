<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard;

interface OutboundPolicyEvaluatorInterface
{
    public function evaluate(
        \Psr\Http\Message\RequestInterface $request,
        RequestPolicyContext $context
    ): PolicyDecision;
}
