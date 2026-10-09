<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class DependencyRules
{
    public function testKernelDoesNotDependOnTypo3(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\HttpGuard'))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('TYPO3'), Selector::inNamespace('Netresearch\NrHttpGuard'));
    }

    public function testPolicyDoesNotDependOnClientOrTransport(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\HttpGuard'))
            ->excluding(
                Selector::inNamespace('Netresearch\HttpGuard\Client'),
                Selector::inNamespace('Netresearch\HttpGuard\Transport'),
            )
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('Netresearch\HttpGuard\Client'),
                Selector::inNamespace('Netresearch\HttpGuard\Transport'),
            );
    }

    public function testPolicyDoesNotDependOnGuzzleHandlers(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\HttpGuard'))
            ->excluding(
                Selector::inNamespace('Netresearch\HttpGuard\Client'),
                Selector::inNamespace('Netresearch\HttpGuard\Transport'),
            )
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('GuzzleHttp\Handler'));
    }

    public function testCommandsDoNotDependOnNativeTransferInternals(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\NrHttpGuard\Command'))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('Netresearch\HttpGuard\Transport'));
    }
}
