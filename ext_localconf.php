<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);
defined('TYPO3') or exit;

use Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility;
use TYPO3\CMS\Core\Http\RequestFactory;

(static function (): void {
    if (!array_key_exists(RequestFactory::class, $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] ?? [])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][RequestFactory::class] = ['className' => RequestFactoryCompatibility::replacementClass()];
    }
})();
