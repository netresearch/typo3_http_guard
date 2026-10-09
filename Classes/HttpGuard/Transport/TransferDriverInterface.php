<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Transport;

interface TransferDriverInterface
{
    public function tick(): void;
}
