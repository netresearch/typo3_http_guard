<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('http-guard:legacy-report', 'Inventories legacy allowlist shapes without creating policy.')]
final class LegacyReportCommand extends AbstractGuardCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->result($this->diagnostics->legacyReport(), $output);
    }
}
