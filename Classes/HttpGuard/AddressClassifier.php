<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

use JsonException;

final class AddressClassifier
{
    /** @var array<int,array<int,array<string,array{class:string,endpoint:string}>>> */
    private array $rules = [4 => [], 6 => []];
    /** @var array<int,array<string,true>> */
    private array $allocated = [];
    /** @var array<string,Cidr> */
    private array $cidrs = [];

    public function __construct()
    {
        $data = $this->loadRules();
        foreach (array_merge($data['iana_special_rules'], $data['supplemental_rules'], $data['provider_hard_denies']) as $rule) {
            if (str_starts_with($rule['cidr'], '::ffff:')) {
                continue;
            }
            $network                                                            = Cidr::parse($rule['cidr'], false);
            $this->rules[$network->family][$network->prefix][$network->network] = ['class' => $rule['class'], 'endpoint' => $rule['endpoint']];
        }
        foreach ([4, 6] as $family) {
            krsort($this->rules[$family], SORT_NUMERIC);
        }
        foreach ($data['ipv6_allocated_global_prefixes'] as $rule) {
            $network                                              = Cidr::parse($rule['cidr'], false);
            $this->allocated[$network->prefix][$network->network] = true;
        }
        krsort($this->allocated, SORT_NUMERIC);
    }

    public function classify(string $ip): AddressClassification
    {
        $canonical = Cidr::address($ip);
        $packed    = (string) inet_pton($canonical);
        $family    = strlen($packed) === 4 ? 4 : 6;
        foreach ($this->rules[$family] as $prefix => $networks) {
            $rule = $networks[Cidr::networkBits($packed, $prefix)] ?? null;
            if ($rule === null) {
                continue;
            }
            $exceptable = $rule['endpoint'] !== 'forbidden';

            return new AddressClassification($canonical, $rule['class'], false, $exceptable, !$exceptable);
        }
        if ($family === 6) {
            $allocated = false;
            foreach ($this->allocated as $prefix => $networks) {
                if (isset($networks[Cidr::networkBits($packed, $prefix)])) {
                    $allocated = true;
                    break;
                }
            }
            if (!$allocated) {
                return new AddressClassification($canonical, 'unallocated_or_non_global_ipv6', false, false, true);
            }
        }

        return new AddressClassification($canonical, 'ordinary_global_unicast', true, true, false);
    }

    public function contains(string $canonicalCidr, string $canonicalIp): bool
    {
        $network = $this->cidrs[$canonicalCidr] ??= Cidr::parse($canonicalCidr);
        $packed  = NativeOperation::attempt(static fn () => inet_pton($canonicalIp));
        if ($packed === false) {
            return false;
        }
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat(chr(0), 10) . chr(255) . chr(255)) {
            $packed = substr($packed, 12);
        }

        return $network->containsPacked($packed);
    }

    /**
     * @return array{iana_special_rules: list<array{cidr: string, class: string, endpoint: string}>, supplemental_rules: list<array{cidr: string, class: string, endpoint: string}>, provider_hard_denies: list<array{cidr: string, class: string, endpoint: string}>, ipv6_allocated_global_prefixes: list<array{cidr: string}>}
     */
    private function loadRules(): array
    {
        $path = dirname(__DIR__, 2) . '/Resources/Private/HttpGuard/data/security-corpus/address-rules.json';
        if (!is_file($path)) {
            throw new PolicyException('configuration_invalid');
        }
        $json = NativeOperation::attempt(static fn () => file_get_contents($path));
        if ($json === false) {
            throw new PolicyException('configuration_invalid');
        }
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new PolicyException('configuration_invalid');
        }
        if (!is_array($data) || ($data['schema_version'] ?? null) !== 1) {
            throw new PolicyException('configuration_invalid');
        }
        $rules = [];
        foreach (['iana_special_rules', 'supplemental_rules', 'provider_hard_denies'] as $name) {
            $rows = $data[$name] ?? null;
            if (!is_array($rows) || !array_is_list($rows) || $rows === []) {
                throw new PolicyException('configuration_invalid');
            }
            $validated = [];
            foreach ($rows as $row) {
                if (!is_array($row) || !is_string($row['cidr'] ?? null) || !is_string($row['class'] ?? null) || preg_match('/^[a-z0-9_]{1,64}$/D', $row['class']) !== 1 || !is_string($row['endpoint'] ?? null) || !in_array(
                    $row['endpoint'],
                    [
                        'forbidden',
                        'normalize_embedded_ipv4_before_all_rules',
                        'requires_bound_endpoint_allowLoopback_and_host_prefix',
                        'requires_bound_endpoint_and_narrow_cidr',
                    ],
                    true,
                )) {
                    throw new PolicyException('configuration_invalid');
                }
                $validated[] = ['cidr' => $row['cidr'], 'class' => $row['class'], 'endpoint' => $row['endpoint']];
            }
            $rules[$name] = $validated;
        }
        $rows = $data['ipv6_allocated_global_prefixes'] ?? null;
        if (!is_array($rows) || !array_is_list($rows) || $rows === []) {
            throw new PolicyException('configuration_invalid');
        }
        $allocated = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['cidr'] ?? null) || Cidr::parse($row['cidr'], false)->family !== 6) {
                throw new PolicyException('configuration_invalid');
            }
            $allocated[] = ['cidr' => $row['cidr']];
        }

        return [
            'iana_special_rules'             => $rules['iana_special_rules'],
            'supplemental_rules'             => $rules['supplemental_rules'],
            'provider_hard_denies'           => $rules['provider_hard_denies'],
            'ipv6_allocated_global_prefixes' => $allocated,
        ];
    }
}
