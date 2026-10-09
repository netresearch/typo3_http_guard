<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

/** @internal Trusted DNS source, independent of search/NSS/HTTP transport. */
final class WireDnsQuery implements DnsQueryInterface
{
    /** @var list<string>|null */
    private ?array $nameservers;
    private readonly DnsPacketCodec $codec;
    /** @param array<array-key,mixed>|null $nameservers */
    public function __construct(
        ?array $nameservers = null,
        private readonly int $port = 53,
        private readonly float $queryTimeoutSeconds = 1.0,
        private readonly string $resolvConfPath = '/etc/resolv.conf'
    )
    {
        if ($port < 1 || $port > 65535 || !is_finite($queryTimeoutSeconds) || $queryTimeoutSeconds <= 0 || $queryTimeoutSeconds > 10) {
            throw new PolicyException('configuration_invalid');
        }
        $this->nameservers = $nameservers === null ? null : self::validateNameservers($nameservers);
        $this->codec = new DnsPacketCodec();
    }
    public function query(string $absoluteFqdn, int $qtype): DnsAnswer
    {
        $id = random_int(0, 65535);
        $query = $this->codec->query($absoluteFqdn, $qtype, $id);
        foreach ($this->nameservers() as $server) {
            $deadline = hrtime(true) / 1000000000.0 + $this->queryTimeoutSeconds;
            try {
                $packet = $this->exchange($server, $query, $deadline, false);
                $answer = $this->codec->decode($packet, $id, $absoluteFqdn, $qtype);
                if ($answer['truncated']) {
                    $packet = $this->exchange($server, $query, $deadline, true);
                    $answer = $this->codec->decode($packet, $id, $absoluteFqdn, $qtype);
                    if ($answer['truncated']) {
                        throw new PolicyException('resolution_unverified');
                    }
                }
                return new DnsAnswer($answer['records'], 'dns', true);
            } catch (PolicyException $e) {
                if ($e->reasonCode() === 'resolution_limit') {
                    throw $e;
                }
            }
        }
        throw new PolicyException('resolution_unverified');
    }
    private function exchange(
        string $server,
        string $query,
        float $deadline,
        bool $tcp
    ): string
    {
        $remaining = $deadline - hrtime(true) / 1000000000.0;
        if ($remaining <= 0) {
            self::fail();
        }
        $authority = str_contains($server, ':') ? '[' . $server . ']' : $server;
        $socket = @stream_socket_client(
            ($tcp ? 'tcp' : 'udp') . '://' . $authority . ':' . $this->port,
            $errno,
            $error,
            $remaining,
            STREAM_CLIENT_CONNECT
        );
        if ($socket === false) {
            self::fail();
        }
        try {
            if (!stream_set_blocking($socket, false)) {
                self::fail();
            }
            $payload = ($tcp ? pack('n', strlen($query)) : '') . $query;
            $written = 0;
            while ($written < strlen($payload)) {
                $this->ready($socket, $deadline, false);
                $count = @fwrite($socket, substr($payload, $written));
                if ($count === false || $count === 0) {
                    self::fail();
                }
                $written += $count;
                if (!$tcp && $written !== strlen($payload)) {
                    self::fail();
                }
            }
            if (!$tcp) {
                $this->ready($socket, $deadline, true);
                $packet = @fread($socket, 65535);
                if ($packet === false || $packet === '') {
                    self::fail();
                }
                return $packet;
            }
            $prefix = $this->read($socket, 2, $deadline);
            $size = self::packetLength($prefix);
            if ($size < 12) {
                self::fail();
            }
            return $this->read($socket, $size, $deadline);
        } finally {
            fclose($socket);
        }
    }
    /** @param resource $socket */
    private function read($socket, int $length, float $deadline): string
    {
        $data = '';
        $remaining = $length;
        while ($remaining > 0) {
            $this->ready($socket, $deadline, true);
            $chunk = @fread($socket, $remaining);
            if ($chunk === false || $chunk === '') {
                self::fail();
            }
            $data .= $chunk;
            $remaining -= strlen($chunk);
        }
        return $data;
    }
    /**
     * @param resource $socket
     */
    private function ready($socket, float $deadline, bool $reading): void
    {
        $remaining = $deadline - hrtime(true) / 1000000000.0;
        if ($remaining <= 0) {
            self::fail();
        }
        $seconds = (int) $remaining;
        $micros = (int) (($remaining - $seconds) * 1000000);
        $read = $reading ? [$socket] : [];
        $write = $reading ? [] : [$socket];
        $except = [];
        if (@stream_select($read, $write, $except, $seconds, $micros) !== 1) {
            self::fail();
        }
    }
    /** @return list<string> */
    private static function systemNameservers(string $path): array
    {
        $text = @file_get_contents($path, false, null, 0, 65537);
        if ($text === false || strlen($text) > 65536) {
            throw new PolicyException('configuration_invalid');
        }
        $servers = [];
        foreach (explode(chr(10), $text) as $line) {
            $line = trim(substr($line, 0, strcspn($line, '#;')));
            if ($line === '') {
                continue;
            }
            $fields = preg_split('/\s+/', $line);
            if ($fields === false) {
                throw new PolicyException('configuration_invalid');
            }
            if ($fields[0] === 'nameserver') {
                if (count($fields) !== 2) {
                    throw new PolicyException('configuration_invalid');
                }
                $servers[] = $fields[1];
            } elseif (!in_array(
                $fields[0],
                ['search', 'domain', 'options', 'sortlist'],
                true
            )) {
                throw new PolicyException('configuration_invalid');
            }
        }
        return array_values(array_unique($servers));
    }
    private static function fail(): never
    {
        throw new PolicyException('resolution_unverified');
    }
    private static function packetLength(string $prefix): int
    {
        $parts = unpack('nlength', $prefix);
        if ($parts === false) {
            self::fail();
        }
        return $parts['length'];
    }
    /**
     * @param array<array-key,mixed> $servers
     * @return list<string>
     */
    private static function validateNameservers(array $servers): array
    {
        if (!array_is_list($servers) || $servers === [] || count($servers) > 3) {
            throw new PolicyException('configuration_invalid');
        }
        $valid = [];
        foreach ($servers as $server) {
            if (!is_string($server)) {
                throw new PolicyException('configuration_invalid');
            }
            try {
                $valid[] = Cidr::address($server);
            } catch (PolicyException) {
                throw new PolicyException('configuration_invalid');
            }
        }
        return array_values(array_unique($valid));
    }
    /** @return list<string> */
    private function nameservers(): array
    {
        return $this->nameservers ??= self::validateNameservers(
            self::systemNameservers($this->resolvConfPath)
        );
    }
}
