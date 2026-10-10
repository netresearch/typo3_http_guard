<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Configuration;

use Netresearch\HttpGuard\GuardConfig;

final class GlobalHttpDefaults
{
    /** @param array<string, mixed> $http
     * @return array<string, mixed> */
    public function apply(array $http, GuardConfig $config): array
    {
        if ($config->mode !== 'enforce') {
            return $http;
        }
        $current     = $http['allow_redirects'] ?? true;
        $coreDefault = ['max' => 5, 'strict' => false];
        if (!array_key_exists('allow_redirects', $http) || $current === true || $current === $coreDefault) {
            $max                     = min(5, $config->data['redirects']['max']);
            $http['allow_redirects'] = $max === 0 ? false : ['max' => $max, 'strict' => false];
        }

        return $http;
    }
}
