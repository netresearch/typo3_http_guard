<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

interface OutboundPolicyEvaluatorInterface
{
    public function evaluate(
        \Psr\Http\Message\RequestInterface $request,
        RequestPolicyContext $context,
    ): PolicyDecision;
}
