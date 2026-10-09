<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Http;

use RuntimeException;

require __DIR__ . '/../Tests/bootstrap.php';
RequestFactoryCompatibility::assertSupported();

/**
 * Static-analysis shell for the excluded, incompatible Core 14 declaration.
 * This bootstrap is never used by production or genuine Core integration tests.
 */
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() === 13) {
    final class GuardedRequestFactory14 extends \TYPO3\CMS\Core\Http\RequestFactory
    {
        use RawRequestGuardTrait;
    }
} else {
    throw new RuntimeException('This analysis harness requires a supported Core 13 version.');
}
