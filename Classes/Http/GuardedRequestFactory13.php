<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Http;

final class GuardedRequestFactory13 extends \TYPO3\CMS\Core\Http\RequestFactory
{
    use RawRequestGuardTrait;
}
