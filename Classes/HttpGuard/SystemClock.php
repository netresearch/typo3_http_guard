<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
    public function monotonic(): float
    {
        return hrtime(true) / 1000000000.0;
    }
}
