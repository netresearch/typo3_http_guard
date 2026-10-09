<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
    public function monotonic(): float;
}
