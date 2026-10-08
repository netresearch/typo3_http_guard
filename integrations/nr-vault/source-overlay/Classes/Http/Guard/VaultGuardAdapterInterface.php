<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);

namespace Netresearch\NrVault\Http\Guard;

use Netresearch\NrVault\Http\CancellableTransport;
use Netresearch\NrVault\Http\SecureHttpClientFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Explicit internal integration seam; never selected by request URLs.
 *
 * @internal
 */
interface VaultGuardAdapterInterface
{
    /**
     * @param array<string, mixed> $platformOptions
     */
    public function create(array $platformOptions): ClientInterface;

    /**
     * @param array<string, mixed> $platformOptions
     */
    public function createCancellable(
        array $platformOptions,
        float $wallClockBudgetSeconds,
        ?float $idleBudgetSeconds,
    ): ?CancellableTransport;

    public function isHostAllowed(string $host): bool;

    public function assertRequestAllowed(RequestInterface $request): void;

    public function tokenFactory(): ?SecureHttpClientFactory;

    public function mode(): string;

    /**
     * Returns false for an observe-mode raw refusal; enforce throws, disabled delegates.
     */
    public function validateRawUri(string $uri): bool;
}
