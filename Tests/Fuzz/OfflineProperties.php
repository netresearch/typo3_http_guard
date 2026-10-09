<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Fuzz;

use Closure;
use GuzzleHttp\Psr7\Request;
use LogicException;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\Cidr;
use Netresearch\HttpGuard\DnsPacketCodec;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\TargetNormalizer;

/** Offline properties: no resolver, client, socket, DNS or HTTP operation is instantiated. */
final class OfflineProperties
{
    private readonly AddressClassifier $classifier;
    private readonly DnsPacketCodec $codec;
    private readonly TargetNormalizer $normalizer;

    public function __construct()
    {
        $this->classifier = new AddressClassifier();
        $this->codec      = new DnsPacketCodec();
        $this->normalizer = new TargetNormalizer();
    }

    public function exercise(string $input): void
    {
        if ($input === '') {
            return;
        }
        $body = substr($input, 1);
        $mode = ord($input[0]) % 6;
        if ($mode === 3) {
            $this->binaryAddress($body);

            return;
        }
        if ($mode === 5) {
            $this->dnsRoundTrip($body);

            return;
        }
        try {
            match ($mode) {
                0 => $this->host($body),
                1 => $this->uri($body),
                2 => $this->cidr($body),
                4 => $this->decodePacket($body),
            };
        } catch (PolicyException $exception) {
            self::check(in_array($exception->reasonCode(), PolicyException::REASONS, true), 'Unknown rejection reason');
        }
    }

    private function host(string $input): void
    {
        $canonical = TargetNormalizer::host($input);
        self::check(TargetNormalizer::host($canonical) === $canonical, 'Host normalization is not idempotent');
        self::check($canonical === strtolower($canonical), 'Canonical host is not lower case');
        self::check(
            !str_ends_with($canonical, '.') && !str_contains($canonical, '%'),
            'Forbidden canonical host syntax',
        );
    }

    private function uri(string $input): void
    {
        $this->normalizer->assertRawUri($input);
        try {
            $request = new Request('GET', $input);
        } catch (\GuzzleHttp\Psr7\Exception\MalformedUriException) {
            // A raw input rejected by the SDK never reaches the policy or transport.
            return;
        }
        $target = $this->normalizer->normalize($request);
        $repeat = $this->normalizer->normalize($target->canonicalRequest);
        self::check(
            $target->origin === $repeat->origin && (string) $target->canonicalRequest->getUri() === (string) $repeat->canonicalRequest->getUri(),
            'Target normalization is not idempotent',
        );
        self::check(in_array($target->scheme, ['http', 'https'], true), 'Forbidden normalized scheme');
        self::check(
            $target->canonicalRequest->getUri()->getUserInfo() === '' && $target->canonicalRequest->getUri()->getFragment() === '',
            'Normalized target contains user info or fragment',
        );
        self::check(
            $target->canonicalRequest->getHeaderLine('Host') === substr($target->origin, strlen($target->scheme) + 3),
            'Canonical Host disagrees with origin',
        );
    }

    private function cidr(string $input): void
    {
        $network = Cidr::parse($input);
        $repeat  = Cidr::parse($network->cidr);
        self::check(
            $network->cidr === $repeat->cidr && $network->network === $repeat->network,
            'CIDR normalization is not idempotent',
        );
        self::check($network->containsNetwork($network), 'Network does not contain itself');
    }

    private function binaryAddress(string $input): void
    {
        $width  = isset($input[0]) && (ord($input[0]) & 1) === 1 ? 16 : 4;
        $packed = substr(str_pad(substr($input, 1), $width, chr(0)), 0, $width);
        $text   = inet_ntop($packed);
        if ($text === false) {
            throw new LogicException('Generated IP has invalid packed size');
        }
        $canonical       = Cidr::address($text);
        $canonicalPacked = inet_pton($canonical);
        if ($canonicalPacked === false) {
            throw new LogicException('Canonical IP is not an address');
        }
        self::check(Cidr::address($canonical) === $canonical, 'Address normalization is not idempotent');
        $prefix        = (isset($input[1]) ? ord($input[1]) : 0) % (strlen($canonicalPacked) * 8 + 1);
        $networkPacked = $canonicalPacked;
        for ($bit = $prefix; $bit < strlen($networkPacked) * 8; ++$bit) {
            $byte                 = intdiv($bit, 8);
            $networkPacked[$byte] = chr(ord($networkPacked[$byte]) & ~(1 << 7 - $bit % 8));
        }
        $networkText = inet_ntop($networkPacked);
        if ($networkText === false) {
            throw new LogicException('Generated network has invalid packed size');
        }
        $network = Cidr::parse($networkText . '/' . $prefix, false);
        self::check($network->contains($canonical), 'Binary prefix rejects its generated member');
        if ($prefix > 0) {
            $outside     = $canonicalPacked;
            $outside[0]  = chr(ord($outside[0]) ^ 128);
            $outsideText = inet_ntop($outside);
            if ($outsideText === false) {
                throw new LogicException('Generated outside address has invalid packed size');
            }
            self::check(!$network->contains($outsideText), 'Binary prefix accepts an outside address');
        }
        $classification = $this->classifier->classify($canonical);
        self::check($classification->canonicalIp === $canonical, 'Classification changes the canonical address');
        self::check(
            !$classification->hardDenied || !$classification->public && !$classification->endpointExceptable,
            'Hard denial permits an exception',
        );
        if (strlen($canonicalPacked) === 4) {
            $mapped = $this->classifier->classify('::ffff:' . $canonical);
            self::check($classification == $mapped, 'Mapped IPv4 changes classification');
        }
    }

    private function decodePacket(string $input): void
    {
        $decoded = $this->codec->decode($input, 1234, 'api.example.', 1);
        self::check(!$decoded['truncated'] || $decoded['records'] === [], 'Truncated DNS response returned addresses');
        foreach ($decoded['records'] as $record) {
            self::check(
                in_array($record['type'], ['A', 'AAAA', 'CNAME'], true),
                'DNS decoder returned an unsupported record',
            );
        }
    }

    private function dnsRoundTrip(string $input): void
    {
        $packed   = substr(str_pad($input, 4, chr(0)), 0, 4);
        $ttl      = isset($input[4]) ? ord($input[4]) : 0;
        $question = "\x03api\x07example\x00" . pack('nn', 1, 1);
        $record   = "\xc0\f" . pack('nnNn', 1, 1, $ttl, 4) . $packed;
        $packet   = pack('nnnnnn', 1234, 0x8180, 1, 1, 0, 0) . $question . $record;
        $decoded  = $this->codec->decode($packet, 1234, 'api.example.', 1);
        self::check(
            $decoded === [
                'records'   => [['host' => 'api.example', 'type' => 'A', 'ttl' => $ttl, 'ip' => inet_ntop($packed)]],
                'truncated' => false,
            ],
            'DNS round trip changed answer bytes or TTL',
        );
        self::rejects(fn () => $this->codec->decode($packet, 1235, 'api.example.', 1));
        self::rejects(fn () => $this->codec->decode($packet, 1234, 'other.example.', 1));
        self::rejects(fn () => $this->codec->decode($packet . chr(0), 1234, 'api.example.', 1));
        self::rejects(fn () => $this->codec->decode(substr($packet, 0, -1), 1234, 'api.example.', 1));
    }

    private static function rejects(Closure $operation): void
    {
        try {
            $operation();
        } catch (PolicyException $exception) {
            self::check(
                $exception->reasonCode() === 'resolution_unverified',
                'Malformed DNS response has wrong rejection reason',
            );

            return;
        }
        throw new LogicException('Malformed DNS response was accepted');
    }

    private static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new LogicException($message);
        }
    }
}
