<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

final class NullDecisionReporter implements DecisionReporterInterface
{
    public function report(DecisionEvent $event): void {}
}
