<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

final class TargetNormalizer
{
    public function normalize(
        \Psr\Http\Message\RequestInterface $request
    ): Target
    {
        $uri = $this->validatedUri($request->getUri());
        $raw = (string) $uri;
        if (strlen($raw) > 8192 || preg_match('/[\x00-\x20\x7f\\\\]/', $raw) || $uri->getUserInfo() !== '' || $uri->getFragment() !== '' || str_contains($raw, '#')) {
            throw new PolicyException('invalid_target');
        }
        $scheme = strtolower($uri->getScheme());
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new PolicyException('scheme_forbidden');
        }
        if (!preg_match("/^[!#\$%&'*+.^_`|~0-9A-Za-z-]+\$/D", $request->getMethod())) {
            throw new PolicyException('invalid_target');
        }
        $host = self::host($uri->getHost());
        $port = $uri->getPort() ?? ($scheme === 'https' ? 443 : 80);
        if ($port < 1 || $port > 65535) {
            throw new PolicyException('invalid_target');
        }
        $headers = $request->getHeader('Host');
        if (count($headers) > 1) {
            throw new PolicyException('authority_mismatch');
        }
        if ($headers !== []) {
            $authority = $headers[0];
            if (!preg_match(
                '/^(\[[0-9a-fA-F:.]+\]|[^:]+)(?::([0-9]+))?$/D',
                $authority,
                $m
            )) {
                throw new PolicyException('authority_mismatch');
            }
            try {
                $hostHeader = self::host($m[1]);
            } catch (PolicyException) {
                throw new PolicyException('authority_mismatch');
            }
            $headerPort = isset($m[2]) ? (int) $m[2] : ($scheme === 'https' ? 443 : 80);
            if ($hostHeader !== $host || $headerPort !== $port || isset($m[2]) && (string) $headerPort !== $m[2]) {
                throw new PolicyException('authority_mismatch');
            }
        }
        $literal = null;
        if (str_contains($host, ':') || preg_match('/^[0-9.]+$/D', $host)) {
            $literal = Cidr::address($host);
        }
        $authority = (str_contains($host, ':') ? '[' . $host . ']' : $host) . ($scheme === 'https' && $port === 443 || $scheme === 'http' && $port === 80 ? '' : ':' . $port);
        $canonicalUri = $uri
            ->withScheme($scheme)
            ->withHost(str_contains($host, ':') ? '[' . $host . ']' : $host)
            ->withPort(
                $scheme === 'https' && $port === 443 || $scheme === 'http' && $port === 80 ? null : $port
            );
        $canonicalRequest = $request->withUri($canonicalUri)->withHeader('Host', $authority);
        return new Target(
            $canonicalRequest,
            $scheme,
            $host,
            $port,
            $scheme . '://' . $authority,
            $literal
        );
    }
    public static function host(string $host): string
    {
        if ($host === '' || strlen($host) > 255 || preg_match('/[^\x21-\x7e]/', $host) || str_contains($host, '%') || str_contains($host, '\\\\')) {
            throw new PolicyException('invalid_target');
        }
        if ($host[0] === '[') {
            if (!str_ends_with($host, ']')) {
                throw new PolicyException('invalid_target');
            }
            $host = substr($host, 1, -1);
        }
        if (str_contains($host, ':')) {
            $packed = @inet_pton($host);
            if ($packed === false || strlen($packed) !== 16) {
                throw new PolicyException('invalid_target');
            }
            return (string) inet_ntop($packed);
        }
        $host = strtolower($host);
        if (str_ends_with($host, '.')) {
            $host = substr($host, 0, -1);
        }
        if ($host === '' || strlen($host) > 253 || str_ends_with($host, '.')) {
            throw new PolicyException('invalid_target');
        }
        if (preg_match(
            '/^(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*$/D',
            $host
        )) {
            return Cidr::address($host);
        }
        foreach (explode('.', $host) as $label) {
            if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $label)) {
                throw new PolicyException('invalid_target');
            }
        }
        return $host;
    }
    public function assertRawUri(string $rawUri): void
    {
        if (strlen($rawUri) > 8192 || preg_match('/[\x00-\x20\x7f\\\\]/', $rawUri) || str_contains($rawUri, '#')) {
            throw new PolicyException('invalid_target');
        }
        if (!preg_match('~^([A-Za-z][A-Za-z0-9+.-]*):~D', $rawUri, $scheme)) {
            throw new PolicyException('invalid_target');
        }
        if (!in_array(strtolower($scheme[1]), ['http', 'https'], true)) {
            throw new PolicyException('scheme_forbidden');
        }
        if (!preg_match('~^https?://([^/?#]*)~iD', $rawUri, $match) || $match[1] === '' || str_contains($match[1], '@')) {
            throw new PolicyException('invalid_target');
        }
        if (!preg_match(
            '/^(\[[0-9a-fA-F:.]+\]|[^:]+)(?::([1-9][0-9]{0,4}))?$/D',
            $match[1],
            $authority
        )) {
            throw new PolicyException('invalid_target');
        }
        self::host($authority[1]);
        if (isset($authority[2]) && (int) $authority[2] > 65535) {
            throw new PolicyException('invalid_target');
        }
    }
    private function validatedUri(
        \Psr\Http\Message\UriInterface $uri
    ): \Psr\Http\Message\UriInterface
    {
        $this->assertRawUri((string) $uri);
        return $uri;
    }
}
