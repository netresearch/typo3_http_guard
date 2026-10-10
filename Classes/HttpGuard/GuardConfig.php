<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * @phpstan-type EndpointConfiguration array{origin:string, allowedCidrs:list<string>, methods:list<string>, redirects:'none'|'same-origin', allowLoopback:bool, purpose:string, owner:string, reviewAfter:?string, expiresAt:?string}
 * @phpstan-type ResolverConfiguration array{staticHosts:array<string,list<string>>, cacheTtlSeconds:int, cacheMaxHosts:int, maxAddresses:int, maxCnameHops:int}
 * @phpstan-type LoggingConfiguration array{allowedSampleRate:int|float, hostMode:'hash'|'plain', hostHmacKeyEnv:?string, denyRateLimitPerMinute:int}
 * @phpstan-type ValidatedConfiguration array{schemaVersion:1, mode:'enforce'|'observe'|'disabled', deniedCidrs:list<string>, endpoints:array<string,EndpointConfiguration>, resolver:ResolverConfiguration, redirects:array{max:int}, tls:array{requireVerification:bool}, logging:LoggingConfiguration}
 */
final readonly class GuardConfig
{
    /** @param ValidatedConfiguration $data */
    private function __construct(public string $mode, public string $revision, public array $data) {}

    /** @param array<array-key,mixed> $data */
    public static function fromArray(array $data): self
    {
        self::keys(
            $data,
            ['schemaVersion', 'mode', 'deniedCidrs', 'endpoints', 'resolver', 'redirects', 'tls', 'logging'],
        );
        $defaults = [
            'schemaVersion' => 1,
            'mode'          => 'enforce',
            'deniedCidrs'   => [],
            'endpoints'     => [],
            'resolver'      => [
                'staticHosts'     => [],
                'cacheTtlSeconds' => 5,
                'cacheMaxHosts'   => 32,
                'maxAddresses'    => 64,
                'maxCnameHops'    => 8,
            ],
            'redirects' => ['max' => 5],
            'tls'       => ['requireVerification' => false],
            'logging'   => ['allowedSampleRate' => 0, 'hostMode' => 'hash', 'hostHmacKeyEnv' => null, 'denyRateLimitPerMinute' => 60],
        ];
        $result = $defaults;
        foreach ($data as $key => $value) {
            if (in_array($key, ['resolver', 'redirects', 'tls', 'logging'], true)) {
                if (!is_array($value)) {
                    self::invalid();
                }
                self::keys($value, array_keys($defaults[$key]));
                $result[$key] = array_replace($defaults[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        if ($result['schemaVersion'] !== 1 || !in_array($result['mode'], ['enforce', 'observe', 'disabled'], true)) {
            self::invalid();
        }
        $result['deniedCidrs'] = self::cidrs($result['deniedCidrs'], false, false);
        if (!is_array($result['resolver']) || !is_array($result['redirects']) || !is_array($result['tls']) || !is_array($result['logging'])) {
            self::invalid();
        }
        $r                    = $result['resolver'];
        $r['cacheTtlSeconds'] = self::integer($r['cacheTtlSeconds'], 0, 5);
        $r['cacheMaxHosts']   = self::integer($r['cacheMaxHosts'], 1, 1024);
        $r['maxAddresses']    = self::integer($r['maxAddresses'], 1, 64);
        $r['maxCnameHops']    = self::integer($r['maxCnameHops'], 0, 8);
        if (!is_array($r['staticHosts'])) {
            self::invalid();
        }
        $hosts = [];
        foreach ($r['staticHosts'] as $host => $ips) {
            if (!is_string($host)) {
                self::invalid();
            }
            try {
                $canonical = TargetNormalizer::host($host);
            } catch (PolicyException) {
                self::invalid();
            }
            if (str_contains($canonical, ':') || preg_match('/^[0-9.]+$/D', $canonical) === 1 || isset($hosts[$canonical]) || !is_array($ips) || !array_is_list($ips) || $ips === [] || count($ips) > $r['maxAddresses']) {
                self::invalid();
            }
            $addresses = [];
            foreach ($ips as $ip) {
                if (!is_string($ip)) {
                    self::invalid();
                }
                try {
                    $addresses[] = Cidr::address($ip);
                } catch (PolicyException) {
                    self::invalid();
                }
            }
            $hosts[$canonical] = array_values(array_unique($addresses));
            sort($hosts[$canonical], SORT_STRING);
        }
        ksort($hosts, SORT_STRING);
        $r['staticHosts']           = $hosts;
        $result['resolver']         = $r;
        $result['redirects']['max'] = self::integer($result['redirects']['max'], 0, 10);
        if (!is_bool($result['tls']['requireVerification'])) {
            self::invalid();
        }
        $logging = $result['logging'];
        if (!is_int($logging['allowedSampleRate']) && !is_float($logging['allowedSampleRate']) || !is_finite((float) $logging['allowedSampleRate']) || $logging['allowedSampleRate'] < 0 || $logging['allowedSampleRate'] > 1 || !in_array($logging['hostMode'], ['hash', 'plain'], true)) {
            self::invalid();
        }
        if ($logging['hostHmacKeyEnv'] !== null && (!is_string($logging['hostHmacKeyEnv']) || preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,127}$/D', $logging['hostHmacKeyEnv']) !== 1)) {
            self::invalid();
        }
        $logging['denyRateLimitPerMinute'] = self::integer($logging['denyRateLimitPerMinute'], 1, 10000);
        $result['logging']                 = $logging;
        if (!is_array($result['endpoints']) || count($result['endpoints']) > 128) {
            self::invalid();
        }
        $profiles = [];
        foreach ($result['endpoints'] as $id => $endpoint) {
            if (!is_string($id) || preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,63}$/D', $id) !== 1 || !is_array($endpoint)) {
                self::invalid();
            }
            self::keys(
                $endpoint,
                [
                    'origin',
                    'allowedCidrs',
                    'methods',
                    'redirects',
                    'allowLoopback',
                    'purpose',
                    'owner',
                    'reviewAfter',
                    'expiresAt',
                ],
            );
            foreach (['origin', 'allowedCidrs', 'methods', 'purpose', 'owner'] as $required) {
                if (!array_key_exists($required, $endpoint)) {
                    self::invalid();
                }
            }
            $endpoint = array_replace(
                ['redirects' => 'none', 'allowLoopback' => false, 'reviewAfter' => null, 'expiresAt' => null],
                $endpoint,
            );
            if (!is_bool($endpoint['allowLoopback']) || !in_array($endpoint['redirects'], ['none', 'same-origin'], true)) {
                self::invalid();
            }
            if (!is_string($endpoint['origin']) || preg_match('~^https?://(?:\[[0-9a-fA-F:.]+\]|[A-Za-z0-9.-]+)(?::[1-9][0-9]{0,4})?$~iD', $endpoint['origin']) !== 1) {
                self::invalid();
            }
            try {
                $target = (new TargetNormalizer())->normalize(new \GuzzleHttp\Psr7\Request('GET', $endpoint['origin']));
            } catch (Throwable) {
                self::invalid();
            }
            $endpoint['origin']       = $target->origin;
            $endpoint['allowedCidrs'] = self::cidrs($endpoint['allowedCidrs'], true, $endpoint['allowLoopback']);
            if (!is_array($endpoint['methods']) || !array_is_list($endpoint['methods']) || $endpoint['methods'] === []) {
                self::invalid();
            }
            $methods = [];
            foreach ($endpoint['methods'] as $method) {
                if (!is_string($method) || strlen($method) > 64 || preg_match("/^[!#\$%&'*+.^_`|~0-9A-Za-z-]+\$/D", $method) !== 1 || strtoupper($method) === 'CONNECT') {
                    self::invalid();
                }
                $methods[] = $method;
            }
            $endpoint['methods'] = array_values(array_unique($methods));
            sort($endpoint['methods'], SORT_STRING);
            $endpoint['purpose'] = self::text($endpoint['purpose'], 200);
            $endpoint['owner']   = self::text($endpoint['owner'], 120);
            if ($endpoint['reviewAfter'] !== null) {
                $endpoint['reviewAfter'] = self::date($endpoint['reviewAfter'], false);
            }
            if ($endpoint['expiresAt'] !== null) {
                $endpoint['expiresAt'] = self::date($endpoint['expiresAt'], true);
            }
            $profiles[$id] = $endpoint;
        }
        ksort($profiles, SORT_STRING);
        $result['endpoints'] = $profiles;
        $result              = [
            'schemaVersion' => 1,
            'mode'          => $result['mode'],
            'deniedCidrs'   => $result['deniedCidrs'],
            'endpoints'     => $profiles,
            'resolver'      => $r,
            'redirects'     => ['max' => $result['redirects']['max']],
            'tls'           => ['requireVerification' => $result['tls']['requireVerification']],
            'logging'       => $logging,
        ];
        $revision = self::revision($result);

        return new self($result['mode'], $revision, $result);
    }

    /**
     * @param array<array-key,mixed> $value
     * @param list<string>           $allowed
     *
     * @phpstan-assert array<string,mixed> $value
     */
    private static function keys(array $value, array $allowed): void
    {
        foreach (array_keys($value) as $key) {
            if (!is_string($key) || !in_array($key, $allowed, true)) {
                self::invalid();
            }
        }
    }

    private static function integer(mixed $value, int $min, int $max): int
    {
        if (!is_int($value) || $value < $min || $value > $max) {
            self::invalid();
        }

        return $value;
    }

    private static function text(mixed $value, int $max): string
    {
        if (!is_string($value) || trim($value) === '' || preg_match('//u', $value) !== 1 || preg_match('/[\x00-\x1f\x7f]/', $value) === 1 || preg_match_all('/./us', $value) > $max) {
            self::invalid();
        }

        return $value;
    }

    private static function date(mixed $value, bool $instant): string
    {
        if (!is_string($value)) {
            self::invalid();
        }
        $pattern = $instant ? '~^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(?:\.[0-9]{1,6})?(Z|[+-][0-9]{2}:[0-9]{2})$~D' : '~^([0-9]{4})-([0-9]{2})-([0-9]{2})$~D';
        if (preg_match($pattern, $value, $m) !== 1 || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            self::invalid();
        }
        if ($instant) {
            if (!isset($m[4], $m[5], $m[6]) || (int) $m[4] > 23 || (int) $m[5] > 59 || (int) $m[6] > 59) {
                self::invalid();
            }
            $zone = end($m);
            if ($zone !== 'Z' && ((int) substr($zone, 1, 2) > 23 || (int) substr($zone, 4, 2) > 59)) {
                self::invalid();
            }
        }
        try {
            new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (Throwable) {
            self::invalid();
        }

        return $value;
    }

    /** @return list<string> */
    private static function cidrs(mixed $values, bool $endpoint, bool $loopback): array
    {
        if (!is_array($values) || !array_is_list($values) || $endpoint && $values === []) {
            self::invalid();
        }
        $networks = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                self::invalid();
            }
            $literal = explode('/', $value)[0];
            if (preg_match('/[^0-9a-fA-F:.]/D', $literal) === 1) {
                self::invalid();
            }
            $raw = NativeOperation::attempt(static fn () => inet_pton($literal));
            if ($raw !== false && strlen($raw) === 16 && substr($raw, 0, 12) === str_repeat(chr(0), 10) . chr(255) . chr(255)) {
                self::invalid();
            }
            try {
                $network = Cidr::parse($value);
            } catch (PolicyException) {
                self::invalid();
            }
            if ($endpoint && $network->prefix < ($network->family === 4 ? 24 : 64)) {
                self::invalid();
            }
            $isLoopback = $network->family === 4 ? Cidr::parse('127.0.0.0/8')->contains((string) inet_ntop($network->network)) : $network->contains('::1');
            if ($endpoint && $isLoopback && (!$loopback || $network->prefix !== ($network->family === 4 ? 32 : 128))) {
                self::invalid();
            }
            $networks[] = $network;
        }
        usort(
            $networks,
            static fn (
                Cidr $a,
                Cidr $b,
            ): int => ($a->family <=> $b->family) !== 0 ? $a->family <=> $b->family : (($a->prefix <=> $b->prefix) !== 0 ? $a->prefix <=> $b->prefix : strcmp($a->cidr, $b->cidr)),
        );
        $minimal = [];
        foreach ($networks as $network) {
            $covered = false;
            foreach ($minimal as $kept) {
                if ($kept->containsNetwork($network)) {
                    $covered = true;
                    break;
                }
            }
            if (!$covered) {
                $minimal[] = $network;
            }
        }

        return array_map(static fn (Cidr $n): string => $n->cidr, $minimal);
    }

    private static function invalid(): never
    {
        throw new PolicyException('configuration_invalid');
    }

    /** @param array<string,mixed> $data */
    private static function revision(array $data): string
    {
        $rulesHash = NativeOperation::attempt(
            static fn () => hash_file(
                'sha256',
                dirname(__DIR__, 2) . '/Resources/Private/HttpGuard/data/security-corpus/address-rules.json',
            ),
        );
        if ($rulesHash === false) {
            self::invalid();
        }

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . ':' . $rulesHash);
    }
}
