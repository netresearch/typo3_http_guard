<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Diagnostics;

final class LegacyConfigurationInventory
{
    /** @param array<string, mixed> $http
     * @return array<string, mixed> */
    public function inspect(array $http): array
    {
        $allowed = $http['allowed_hosts'] ?? [];
        $flat = 0;
        $nested = 0;
        $nestedHosts = 0;
        $invalid = 0;
        if (!is_array($allowed)) {
            $invalid++;
            $allowed = [];
        }
        foreach ($allowed as $entry) {
            if (is_string($entry)) {
                $flat++;
            } elseif (is_array($entry)) {
                $nested++;
                foreach ($entry as $host) {
                    if (is_string($host)) {
                        $nestedHosts++;
                    } else {
                        $invalid++;
                    }
                }
            } else {
                $invalid++;
            }
        }
        return [
            'automaticGrantsCreated' => 0,
            'sources' => [
                [
                    'location' => 'HTTP.allowed_hosts',
                    'semantics' => 'flat Vault legacy entries',
                    'entries' => $flat,
                ],
                [
                    'location' => 'HTTP.allowed_hosts[context]',
                    'semantics' => 'Core context lists',
                    'contexts' => $nested,
                    'entries' => $nestedHosts,
                ],
            ],
            'invalidEntries' => $invalid,
            'requiredEndpointFields' => ['origin', 'allowedCidrs', 'methods', 'purpose', 'owner'],
            'missingLegacyFields' => ['scheme', 'port', 'addressRanges', 'methods', 'purpose', 'owner'],
            'policyChanged' => false,
        ];
    }
}
