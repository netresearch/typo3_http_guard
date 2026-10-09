<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);

namespace Netresearch\NrHttpGuard\Http;

require __DIR__ . '/../Tests/bootstrap.php';

/**
 * Static-analysis shell for the excluded, incompatible Core 14 declaration.
 * This bootstrap is never used by production or genuine Core integration tests.
 */
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getVersion() === '13.4.35' && !(new \ReflectionClass(\TYPO3\CMS\Core\Http\RequestFactory::class))->isReadOnly()) {
    final class GuardedRequestFactory14 extends \TYPO3\CMS\Core\Http\RequestFactory
    {
        use RawRequestGuardTrait;
    }
} else {
    throw new \RuntimeException('This analysis harness requires actual Core 13.4.35.');
}
