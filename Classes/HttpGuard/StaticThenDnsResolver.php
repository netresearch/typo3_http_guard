<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

use Throwable;

final class StaticThenDnsResolver implements ResolverInterface
{
    /**
     * @var array<string,array{expires:float,resolution:Resolution}>
     */
    private array $cache = [];

    public function __construct(
        private readonly GuardConfig $config,
        private readonly DnsQueryInterface $query,
        private readonly ClockInterface $clock,
    ) {}

    public function resolve(string $canonicalHost): Resolution
    {
        try {
            $host = TargetNormalizer::host($canonicalHost);
        } catch (PolicyException) {
            throw new PolicyException('resolution_unverified');
        }
        if (str_contains($host, ':') || preg_match('/^[0-9.]+$/D', $host) === 1) {
            throw new PolicyException('resolution_unverified');
        }
        $settings = $this->config->data['resolver'];
        if (isset($settings['staticHosts'][$host])) {
            return new Resolution(
                $settings['staticHosts'][$host],
                'static',
                null,
                hash('sha256', $this->config->revision . ':' . $host),
            );
        }
        if (isset($this->cache[$host])) {
            if ($this->clock->monotonic() < $this->cache[$host]['expires']) {
                return $this->cache[$host]['resolution'];
            }
            unset($this->cache[$host]);
        }
        $current                       = $host;
        $seen                          = [];
        $chain                         = [];
        $aliases                       = [];
        $addressRecords                = [];
        $ttl                           = null;
        [$sources, $expiries, $expiry] = [[], [], null];
        while (true) {
            if (isset($seen[$current])) {
                throw new PolicyException('resolution_limit');
            }
            $seen[$current] = true;
            foreach ([1, 28, 5] as $qtype) {
                try {
                    [$started, $answer] = [$this->clock->monotonic(), $this->query->query($current . '.', $qtype)];
                } catch (Throwable) {
                    throw new PolicyException('resolution_unverified');
                }
                if (!$answer->complete || !array_is_list($answer->records)) {
                    throw new PolicyException('resolution_unverified');
                }
                $sources[$answer->source] = true;
                foreach ($answer->records as $record) {
                    if (!is_array($record) || !isset($record['host'], $record['type'], $record['ttl']) || !is_string($record['host']) || !is_string($record['type']) || !is_int($record['ttl']) || $record['ttl'] < 0 || !in_array($record['type'], ['A', 'AAAA', 'CNAME'], true)) {
                        throw new PolicyException('resolution_unverified');
                    }
                    try {
                        $owner = TargetNormalizer::host($record['host']);
                    } catch (PolicyException) {
                        throw new PolicyException('resolution_unverified');
                    }
                    if ($record['type'] === 'CNAME') {
                        if (!isset($record['target']) || !is_string($record['target'])) {
                            throw new PolicyException('resolution_unverified');
                        }
                        try {
                            $target = TargetNormalizer::host($record['target']);
                        } catch (PolicyException) {
                            throw new PolicyException('resolution_unverified');
                        }
                        if (str_contains($target, ':') || preg_match('/^[0-9.]+$/D', $target) === 1) {
                            throw new PolicyException('resolution_unverified');
                        }
                        if (isset($aliases[$owner]) && $aliases[$owner]['target'] !== $target) {
                            throw new PolicyException('resolution_unverified');
                        }
                        [$aliases[$owner], $expiries[$owner]['CNAME']] = [
                            [
                                'target' => $target,
                                'ttl'    => min($record['ttl'], $aliases[$owner]['ttl'] ?? $record['ttl']),
                            ],
                            min($expiries[$owner]['CNAME'] ?? INF, $started + $record['ttl']),
                        ];
                    } else {
                        $field = $record['type'] === 'A' ? 'ip' : 'ipv6';
                        if (!isset($record[$field]) || !is_string($record[$field])) {
                            throw new PolicyException('resolution_unverified');
                        }
                        $packed = NativeOperation::attempt(static fn () => inet_pton($record[$field]));
                        if ($packed === false || strlen($packed) !== ($record['type'] === 'A' ? 4 : 16)) {
                            throw new PolicyException('resolution_unverified');
                        }
                        try {
                            $ip = Cidr::address($record[$field]);
                        } catch (PolicyException) {
                            throw new PolicyException('resolution_unverified');
                        }
                        [$addressRecords[$owner][$ip], $expiries[$owner][$ip]] = [
                            min($record['ttl'], $addressRecords[$owner][$ip] ?? $record['ttl']),
                            min($expiries[$owner][$ip] ?? INF, $started + $record['ttl']),
                        ];
                    }
                }
            }
            if (isset($aliases[$current])) {
                if (isset($addressRecords[$current])) {
                    throw new PolicyException('resolution_unverified');
                }
                [$alias, $expiry] = [$aliases[$current], min($expiry ?? INF, $expiries[$current]['CNAME'])];
                $ttl              = $ttl === null ? $alias['ttl'] : min($ttl, $alias['ttl']);
                $chain[]          = $alias['target'];
                if (count($chain) > $settings['maxCnameHops']) {
                    throw new PolicyException('resolution_limit');
                }
                $current = $alias['target'];
                continue;
            }
            $used = $addressRecords[$current] ?? [];
            if ($used === []) {
                throw new PolicyException('resolution_unverified');
            }
            if (count($used) > $settings['maxAddresses']) {
                throw new PolicyException('resolution_limit');
            }
            foreach ($used as $ip => $recordTtl) {
                [$ttl, $expiry] = [$ttl === null ? $recordTtl : min($ttl, $recordTtl), min($expiry ?? INF, $expiries[$current][$ip])];
            }
            $resolution = new Resolution(
                array_keys($used),
                implode('+', array_keys($sources)),
                min($ttl, (int) floor(max(0, $expiry - $this->clock->monotonic()))),
                bin2hex(random_bytes(12)),
                $chain,
            );
            $cacheTtl = min($settings['cacheTtlSeconds'], $resolution->ttlSeconds ?? 0);
            if ($cacheTtl > 0) {
                while (count($this->cache) >= $settings['cacheMaxHosts']) {
                    $this->evictOldest();
                }
                $this->cache[$host] = ['expires' => $this->clock->monotonic() + $cacheTtl, 'resolution' => $resolution];
            }

            return $resolution;
        }
    }

    private function evictOldest(): void
    {
        $oldest = array_key_first($this->cache);
        if ($oldest !== null) {
            unset($this->cache[$oldest]);
        }
    }
}
