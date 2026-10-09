<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard\Transport;

use GuzzleHttp\Promise\Utils;
final class TransferDriver implements TransferDriverInterface
{
    /** @var array<int,TransferLease> */
    private array $leases = [];
    private int $created = 0;
    private int $released = 0;
    private int $peak = 0;
    public function register(TransferLease $lease): void
    {
        $this->leases[spl_object_id($lease)] = $lease;
        ++$this->created;
        $this->peak = max($this->peak, count($this->leases));
    }
    public function release(TransferLease $lease): void
    {
        if (isset($this->leases[spl_object_id($lease)])) {
            unset($this->leases[spl_object_id($lease)]);
            ++$this->released;
        }
    }
    public function tick(): void
    {
        foreach (array_values($this->leases) as $lease) {
            $lease->tick();
        }
        Utils::queue()->run();
    }
    public function waitFor(TransferLease $lease): void
    {
        while (!$lease->settled()) {
            $this->tick();
        }
        Utils::queue()->run();
    }
    /** @return array{created:int,released:int,active:int,peak:int,nativeConstructed:int} */
    public function counters(): array
    {
        return [
            'created' => $this->created,
            'released' => $this->released,
            'active' => count($this->leases),
            'peak' => $this->peak,
            'nativeConstructed' => $this->nativeConstructed,
        ];
    }
    private int $nativeConstructed = 0;
    public function nativeConstructed(): void
    {
        ++$this->nativeConstructed;
    }
}
