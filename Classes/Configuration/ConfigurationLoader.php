<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Configuration;

use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyException;

final class ConfigurationLoader
{
    private ?GuardConfig $loaded = null;

    public function load(): GuardConfig
    {
        if ($this->loaded instanceof GuardConfig) {
            return $this->loaded;
        }
        $configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        if (!is_array($configuration)) {
            throw new PolicyException('configuration_invalid');
        }
        $extensions = $configuration['EXTCONF'] ?? [];
        if (!is_array($extensions)) {
            throw new PolicyException('configuration_invalid');
        }
        $data = $extensions['nr_http_guard'] ?? [];
        if (!is_array($data)) {
            throw new PolicyException('configuration_invalid');
        }

        return $this->loaded = GuardConfig::fromArray($data);
    }
}
