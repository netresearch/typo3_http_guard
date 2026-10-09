<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Command;

use Netresearch\NrHttpGuard\Diagnostics\DiagnosticResult;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractGuardCommand extends Command
{
    public function __construct(protected readonly DiagnosticsService $diagnostics)
    {
        parent::__construct();
    }

    protected function result(DiagnosticResult $result, OutputInterface $output): int
    {
        $output->writeln(json_encode($result->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $result->exitCode;
    }
}
