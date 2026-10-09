<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Diagnostics;

use GuzzleHttp\Promise\PromiseInterface;
use Netresearch\HttpGuard\PolicyException;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Console\Input\ArgvInput;

final class DiagnosticBootState
{
    public ?string $failureReason = null;

    /** @param array<array-key, mixed> $argv */
    public function isDiagnosticInvocation(array $argv): bool
    {
        if (PHP_SAPI !== 'cli' || !array_is_list($argv)) {
            return false;
        }
        foreach ($argv as $argument) {
            if (!is_string($argument)) {
                return false;
            }
        }
        $command = (new ArgvInput($argv))->getFirstArgument();

        return in_array(
            $command,
            ['http-guard:doctor', 'http-guard:config-check', 'http-guard:policy-check', 'http-guard:legacy-report'],
            true,
        );
    }

    public function recordAndBlock(string $reason): void
    {
        $this->failureReason = $reason;
        $configuration       = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        if (!is_array($configuration)) {
            $configuration = [];
        }
        $http = $configuration['HTTP'] ?? [];
        if (!is_array($http)) {
            $http = [];
        }
        $http['handler'] = [
            'nr/http-guard-diagnostic-denial' => static fn (
                callable $next,
            ): callable => static function (RequestInterface $request, array $options) use ($reason): PromiseInterface {
                throw new PolicyException($reason);
            },
        ];
        $configuration['HTTP']      = $http;
        $GLOBALS['TYPO3_CONF_VARS'] = $configuration;
    }
}
