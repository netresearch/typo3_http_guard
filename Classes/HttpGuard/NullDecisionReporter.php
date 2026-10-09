<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

final class NullDecisionReporter implements DecisionReporterInterface
{
    public function report(DecisionEvent $event): void
    {
    }
}
