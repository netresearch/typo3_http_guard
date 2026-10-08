<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

interface ResolverInterface
{
    public function resolve(string $canonicalHost): Resolution;
}
