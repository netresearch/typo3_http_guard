<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('http-guard:policy-check', 'Evaluates a URL without sending HTTP; DNS can be disabled.')]
final class PolicyCheckCommand extends AbstractGuardCommand
{
    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::REQUIRED);
        $this->addOption('endpoint', null, InputOption::VALUE_REQUIRED);
        $this->addOption('no-dns', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url      = $input->getArgument('url');
        $endpoint = $input->getOption('endpoint');
        $noDns    = $input->getOption('no-dns');
        if (!is_string($url) || $endpoint !== null && !is_string($endpoint) || !is_bool($noDns)) {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                'The URL and endpoint must be strings and --no-dns must be a boolean.',
            );
        }

        return $this->result($this->diagnostics->policyCheck($url, $endpoint, $noDns), $output);
    }
}
