<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard;

/** @internal Bounded DNS wire format parser; no resolver or transport fallback. */
final class DnsPacketCodec
{
    public function query(string $absoluteFqdn, int $qtype, int $id): string
    {
        if (!in_array($qtype, [1, 28, 5], true) || $id < 0 || $id > 65535 || !str_ends_with($absoluteFqdn, '.')) {
            throw new PolicyException('resolution_unverified');
        }
        $host = substr($absoluteFqdn, 0, -1);
        try {
            if (TargetNormalizer::host($host) !== $host || str_contains($host, ':')) {
                throw new PolicyException('resolution_unverified');
            }
        } catch (PolicyException) {
            throw new PolicyException('resolution_unverified');
        }
        $name = '';
        foreach (explode('.', $host) as $label) {
            $name .= $this->labelLength($label) . $label;
        }
        $name .= chr(0);

        return pack('nnnnnn', $id, 0x100, 1, 0, 0, 0) . $name . pack('nn', $qtype, 1);
    }

    /**
     * @return array{records:list<array{host:string,type:string,ttl:int,ip?:string,ipv6?:string,target?:string}>,truncated:bool}
     */
    public function decode(string $packet, int $id, string $absoluteFqdn, int $qtype): array
    {
        $length = strlen($packet);
        if ($length < 12 || $length > 65535) {
            $this->fail();
        }
        $header = $this->numbers('nid/nflags/nqd/nan/nns/nar', substr($packet, 0, 12));
        if ($header['id'] !== $id || ($header['flags'] & 0x8000) === 0 || ($header['flags'] & 0x7800) !== 0 || ($header['flags'] & 0xF) !== 0 || $header['qd'] !== 1) {
            $this->fail();
        }
        $offset = 12;
        $name   = $this->name($packet, $offset);
        $this->bounds($packet, $offset, 4);
        $question = $this->numbers('ntype/nclass', substr($packet, $offset, 4));
        $offset += 4;
        if ($name !== strtolower(substr($absoluteFqdn, 0, -1)) || $question['type'] !== $qtype || $question['class'] !== 1) {
            $this->fail();
        }
        if (($header['flags'] & 0x200) !== 0) {
            return ['records' => [], 'truncated' => true];
        }
        if ($header['an'] + $header['ns'] + $header['ar'] > 4096) {
            throw new PolicyException('resolution_limit');
        }
        $records = [];
        foreach (['an', 'ns', 'ar'] as $section) {
            for ($i = 0; $i < $header[$section]; ++$i) {
                $owner = $this->name($packet, $offset);
                $this->bounds($packet, $offset, 10);
                $rr = $this->numbers('ntype/nclass/Nttl/nlength', substr($packet, $offset, 10));
                $offset += 10;
                $end = $offset + $rr['length'];
                $this->bounds($packet, $offset, $rr['length']);
                $record = null;
                if (in_array($rr['type'], [1, 28, 5], true)) {
                    if ($rr['class'] !== 1) {
                        $this->fail();
                    }
                    $record = [
                        'host' => $owner,
                        'type' => [1 => 'A', 28 => 'AAAA', 5 => 'CNAME'][$rr['type']],
                        'ttl'  => $rr['ttl'],
                    ];
                    if ($rr['type'] === 5) {
                        $cursor           = $offset;
                        $record['target'] = $this->name($packet, $cursor);
                        if ($cursor !== $end) {
                            $this->fail();
                        }
                    } else {
                        $expected = $rr['type'] === 1 ? 4 : 16;
                        if ($rr['length'] !== $expected) {
                            $this->fail();
                        }
                        $record[$rr['type'] === 1 ? 'ip' : 'ipv6'] = (string) inet_ntop(substr($packet, $offset, $expected));
                    }
                }
                if ($section === 'an' && $rr['type'] === 39) {
                    $this->fail();
                }
                if ($section === 'an' && $record !== null) {
                    $records[] = $record;
                }
                $offset = $end;
            }
        }
        if ($offset !== $length) {
            $this->fail();
        }

        return ['records' => $records, 'truncated' => false];
    }

    private function name(string $packet, int &$offset): string
    {
        $position = $offset;
        $consumed = null;
        $labels   = [];
        $visited  = [];
        $expanded = 1;
        while (true) {
            if (isset($visited[$position]) || count($visited) > 128) {
                $this->fail();
            }
            $visited[$position] = true;
            $this->bounds($packet, $position, 1);
            $byte = ord($packet[$position]);
            if (($byte & 0xC0) === 0xC0) {
                $this->bounds($packet, $position, 2);
                $pointer = ($byte & 0x3F) << 8 | ord($packet[$position + 1]);
                if ($pointer < 12 || $pointer >= $position) {
                    $this->fail();
                }
                $consumed ??= $position + 2;
                $position = $pointer;
                continue;
            }
            if (($byte & 0xC0) !== 0 || $byte > 63) {
                $this->fail();
            }
            ++$position;
            if ($byte === 0) {
                $offset = $consumed ?? $position;

                return strtolower(implode('.', $labels));
            }
            $this->bounds($packet, $position, $byte);
            $label = substr($packet, $position, $byte);
            if (preg_match('/[^A-Za-z0-9_-]/D', $label) === 1) {
                $this->fail();
            }
            $expanded += $byte + 1;
            if ($expanded > 255) {
                $this->fail();
            }
            $labels[] = $label;
            $position += $byte;
        }
    }

    private function bounds(string $packet, int $offset, int $count): void
    {
        if ($offset < 0 || $count < 0 || $offset > strlen($packet) - $count) {
            $this->fail();
        }
    }

    private function fail(): never
    {
        throw new PolicyException('resolution_unverified');
    }

    private function labelLength(string $label): string
    {
        $length = strlen($label);
        if ($length < 1 || $length > 63) {
            $this->fail();
        }

        return chr($length);
    }

    /** @return array<string,int> */
    private function numbers(string $format, string $data): array
    {
        $unpacked = unpack($format, $data);
        if ($unpacked === false) {
            $this->fail();
        }
        $result = [];
        foreach ($unpacked as $name => $value) {
            if (!is_string($name) || !is_int($value)) {
                $this->fail();
            }
            $result[$name] = $value;
        }

        return $result;
    }
}
