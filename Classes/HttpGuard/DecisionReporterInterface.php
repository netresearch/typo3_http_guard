<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

interface DecisionReporterInterface
{
    public function report(DecisionEvent $event): void;
}
