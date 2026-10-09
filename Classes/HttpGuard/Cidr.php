<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard;

/** Binary canonical network helper. IPv4-mapped candidates use IPv4 membership. */
final readonly class Cidr
{
    private function __construct(
        public string $cidr,
        public string $network,
        public int $prefix,
        public int $family
    )
    {
    }
    public static function address(string $address): string
    {
        if ($address === '' || preg_match('/[^0-9a-fA-F:.]/D', $address) === 1) {
            throw new PolicyException('invalid_target');
        }
        if (!str_contains($address, ':') && !(preg_match(
            '/^(0|[1-9][0-9]{0,2})(\.(0|[1-9][0-9]{0,2})){3}$/D',
            $address
        ) === 1)) {
            throw new PolicyException('invalid_target');
        }
        $packed = NativeOperation::attempt(static fn() => inet_pton($address));
        if ($packed === false) {
            throw new PolicyException('invalid_target');
        }
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat(chr(0), 10) . chr(255) . chr(255)) {
            $packed = substr($packed, 12);
        }
        $text = (string) inet_ntop($packed);
        return strlen($packed) === 16 && str_contains($text, '.') ? self::ipv6($packed) : $text;
    }
    public static function parse(string $value, bool $strict = true): self
    {
        if (!(preg_match('~^([^/]+)/(0|[1-9][0-9]{0,2})$~D', $value, $parts) === 1)) {
            throw new PolicyException('configuration_invalid');
        }
        $raw = NativeOperation::attempt(static fn() => inet_pton($parts[1]));
        if ($raw === false || !str_contains($parts[1], ':') && self::address($parts[1]) !== $parts[1]) {
            throw new PolicyException('configuration_invalid');
        }
        $prefix = (int) $parts[2];
        $max = strlen($raw) * 8;
        if ($prefix > $max || $strict && (string) inet_ntop($raw) !== $parts[1]) {
            throw new PolicyException('configuration_invalid');
        }
        $network = self::mask($raw, $prefix);
        if ($network !== $raw) {
            throw new PolicyException('configuration_invalid');
        }
        if (strlen($raw) === 16 && substr($raw, 0, 12) === str_repeat(chr(0), 10) . chr(255) . chr(255) && $prefix >= 96) {
            $raw = substr($raw, 12);
            $prefix -= 96;
        }
        return new self(
            (string) inet_ntop($raw) . '/' . $prefix,
            $raw,
            $prefix,
            strlen($raw) === 4 ? 4 : 6
        );
    }
    public function contains(string $address): bool
    {
        try {
            $packed = (string) inet_pton(self::address($address));
        } catch (PolicyException) {
            return false;
        }
        return $this->containsPacked($packed);
    }
    public function containsNetwork(self $other): bool
    {
        return $other->family === $this->family && $other->prefix >= $this->prefix && self::mask($other->network, $this->prefix) === $this->network;
    }
    private static function mask(string $packed, int $bits): string
    {
        $full = intdiv($bits, 8);
        $partial = $bits % 8;
        $result = substr($packed, 0, $full);
        if ($partial !== 0) {
            $result .= chr(ord($packed[$full]) & 255 << 8 - $partial);
            ++$full;
        }
        return $result . str_repeat(chr(0), strlen($packed) - $full);
    }
    private static function ipv6(string $packed): string
    {
        $words = self::words($packed);
        $bestStart = -1;
        $bestLength = 0;
        for ($i = 0; $i < 8; ++$i) {
            if ($words[$i] !== 0) {
                continue;
            }
            $start = $i;
            while ($i < 8 && $words[$i] === 0) {
                ++$i;
            }
            $length = $i - $start;
            if ($length > $bestLength) {
                $bestStart = $start;
                $bestLength = $length;
            }
        }
        $hex = array_map(static fn(int $word): string => dechex($word), $words);
        if ($bestLength < 2) {
            return implode(':', $hex);
        }
        return implode(':', array_slice($hex, 0, $bestStart)) . '::' . implode(':', array_slice($hex, $bestStart + $bestLength));
    }
    public function containsPacked(string $packed): bool
    {
        if (strlen($packed) !== strlen($this->network)) {
            return false;
        }
        $full = intdiv($this->prefix, 8);
        $partial = $this->prefix % 8;
        return ($full === 0 || strncmp($packed, $this->network, $full) === 0) && ($partial === 0 || ((ord($packed[$full]) ^ ord($this->network[$full])) & 255 << 8 - $partial) === 0);
    }
    /** @internal Packed network index for reviewed prefixes. */
    public static function networkBits(string $packed, int $prefix): string
    {
        return self::mask($packed, $prefix);
    }
    /** @return list<int> */
    private static function words(string $packed): array
    {
        $words = unpack('n8', $packed);
        if ($words === false) {
            throw new PolicyException('invalid_target');
        }
        return array_values($words);
    }
}
