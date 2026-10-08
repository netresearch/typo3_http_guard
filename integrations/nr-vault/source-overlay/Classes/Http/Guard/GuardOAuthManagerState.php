<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);

namespace Netresearch\NrVault\Http\Guard;

use Netresearch\NrVault\Http\OAuth\OAuthTokenManager;

/**
 * Shared lazy token-manager cache held by immutable Vault client clones.
 *
 * @internal
 */
final class GuardOAuthManagerState
{
    public ?OAuthTokenManager $manager = null;
}
