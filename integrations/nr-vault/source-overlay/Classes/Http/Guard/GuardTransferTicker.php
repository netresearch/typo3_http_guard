<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);

namespace Netresearch\NrVault\Http\Guard;

use Netresearch\HttpGuard\Transport\TransferDriverInterface;
use Netresearch\NrVault\Http\TransportTickerInterface;

/**
 * @internal Drives only the library client paired with this driver.
 */
final readonly class GuardTransferTicker implements TransportTickerInterface
{
    public function __construct(private TransferDriverInterface $driver) {}

    public function tick(): void
    {
        $this->driver->tick();
    }
}
