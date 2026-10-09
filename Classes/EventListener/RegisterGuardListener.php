<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\EventListener;

use Closure;
use Netresearch\NrHttpGuard\Http\MiddlewareRegistry;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;

final readonly class RegisterGuardListener
{
    /** @param Closure(): MiddlewareRegistry $registry */
    public function __construct(
        private Closure $registry,
        private \Netresearch\NrHttpGuard\Diagnostics\DiagnosticBootState $boot,
    ) {}

    public function __invoke(BootCompletedEvent $event): void
    {
        try {
            ($this->registry)()->register();
        } catch (\Netresearch\HttpGuard\PolicyException $exception) {
            if (!$this->boot->isDiagnosticInvocation($_SERVER['argv'] ?? []) || !in_array(
                $exception->reasonCode(),
                ['configuration_invalid', 'transport_unsupported', 'proxy_unsupported'],
                true,
            )) {
                throw $exception;
            }
            $this->boot->recordAndBlock($exception->reasonCode());
        }
    }
}
