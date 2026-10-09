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

#[AsCommand('http-guard:config-check', 'Validates the deployed HTTP Guard schema offline.')]
final class ConfigCheckCommand extends AbstractGuardCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->result($this->diagnostics->configCheck(), $output);
    }
}
