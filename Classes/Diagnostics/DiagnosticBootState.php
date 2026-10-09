<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
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

    /** @param list<string> $argv */
    public function isDiagnosticInvocation(array $argv): bool
    {
        if (PHP_SAPI !== 'cli') {
            return false;
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
        $this->failureReason                           = $reason;
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = [
            'nr/http-guard-diagnostic-denial' => static function (callable $next) use ($reason): callable {
                return static function (RequestInterface $request, array $options) use ($reason): PromiseInterface {
                    throw new PolicyException($reason);
                };
            },
        ];
    }
}
