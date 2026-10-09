<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard;

interface DecisionReporterInterface
{
    public function report(DecisionEvent $event): void;
}
