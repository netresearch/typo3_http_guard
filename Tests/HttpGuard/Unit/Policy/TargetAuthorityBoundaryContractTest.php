<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\TargetNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class TargetAuthorityBoundaryContractTest extends TestCase
{
    public function testLongestDnsNamesAndUriRemainUsableAtExactLimits(): void
    {
        $host = str_repeat('a', 63) . '.' . str_repeat('b', 63) . '.' . str_repeat('c', 63) . '.' . str_repeat('d', 61);
        self::assertSame(253, strlen($host));
        self::assertSame($host, TargetNormalizer::host(strtoupper($host) . '.'));
        $prefix = 'https://api.example/';
        (new TargetNormalizer())->assertRawUri($prefix . str_repeat('p', 8192 - strlen($prefix)));
        self::assertSame($host, (new TargetNormalizer())->normalize(new Request('GET', 'https://' . $host))->host);
    }

    #[DataProvider('invalidAuthorities')]
    public function testRawAuthorityRejectsAliasAmbiguityAndOversizedInput(string $uri): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('invalid_target');
        (new TargetNormalizer())->assertRawUri($uri);
    }

    public static function invalidAuthorities(): iterable
    {
        yield 'uri 8193' => ['https://api.example/' . str_repeat('p', 8193 - strlen('https://api.example/'))];
        foreach (['0', '00', '080', '65536', '100000', '-1', '+80'] as $port) {
            yield 'port ' . $port => ['http://api.example:' . $port];
        }
        foreach ([
            str_repeat('a', 64) . '.example',
            str_repeat('a', 63) . '.' . str_repeat('b', 63) . '.' . str_repeat('c', 63) . '.' . str_repeat('d', 62),
            '[127.0.0.1]',
            '[::1',
            'api.example..',
            'api_example',
            '0127.0.0.1',
            '0x7f.0.0.1',
        ] as $host) {
            yield 'host ' . $host => ['https://' . $host];
        }
        yield 'userinfo' => ['https://user@api.example'];
    }

    #[DataProvider('canonicalTargets')]
    public function testCanonicalAuthorityPreservesMethodPathQueryAndLiteralIdentity(
        string $uri,
        string $origin,
        string $host,
        int $port,
        ?string $literal,
    ): void {
        $target = (new TargetNormalizer())->normalize(new Request('POST', $uri . '/path?q=one'));
        self::assertSame($origin, $target->origin);
        self::assertSame($host, $target->host);
        self::assertSame($port, $target->port);
        self::assertSame($literal, $target->literalIp);
        self::assertSame('POST', $target->canonicalRequest->getMethod());
        self::assertSame('/path', $target->canonicalRequest->getUri()->getPath());
        self::assertSame('q=one', $target->canonicalRequest->getUri()->getQuery());
        self::assertSame(substr($origin, strpos($origin, '://') + 3), $target->canonicalRequest->getHeaderLine('Host'));
        self::assertSame($origin . '/path?q=one', (string) $target->canonicalRequest->getUri());
    }

    public static function canonicalTargets(): iterable
    {
        yield 'HTTPS default' => ['HTTPS://API.Example.:443', 'https://api.example', 'api.example', 443, null];
        yield 'HTTP default' => ['http://API.Example.:80', 'http://api.example', 'api.example', 80, null];
        yield 'upper port' => ['https://api.example:65535', 'https://api.example:65535', 'api.example', 65535, null];
        yield 'lower port' => ['http://api.example:1', 'http://api.example:1', 'api.example', 1, null];
        yield 'IPv6' => [
            'https://[2001:4860:0:0::8888]:8443',
            'https://[2001:4860::8888]:8443',
            '2001:4860::8888',
            8443,
            '2001:4860::8888',
        ];
        yield 'IPv4' => ['http://203.0.115.7:8080', 'http://203.0.115.7:8080', '203.0.115.7', 8080, '203.0.115.7'];
    }

    #[DataProvider('mismatchedHeaders')]
    public function testRequestHostHeaderMustExactlyMatchCanonicalAuthority(array $headers): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('authority_mismatch');
        (new TargetNormalizer())->normalize(new Request('GET', 'https://api.example:8443', ['Host' => $headers]));
    }

    public static function mismatchedHeaders(): iterable
    {
        yield 'multiple values' => [['api.example:8443', 'api.example:8443']];
        yield 'foreign host' => [['other.example:8443']];
        yield 'missing custom port' => [['api.example']];
        yield 'wrong port' => [['api.example:443']];
        yield 'leading zero port' => [['api.example:08443']];
        yield 'empty port' => [['api.example:']];
        yield 'bad IPv6 bracket' => [['[::1:8443']];
        yield 'invalid host label' => [['bad_host:8443']];
    }

    public function testInvalidMethodFromPsrImplementationFailsBeforeCanonicalRequestIsIssued(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getUri')->willReturn(new Uri('https://api.example'));
        $request->method('getMethod')->willReturn('GET' . chr(10));
        $request->expects(self::never())->method('withUri');
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('invalid_target');
        (new TargetNormalizer())->normalize($request);
    }

    #[DataProvider('invalidBracketedHosts')]
    public function testBracketedHostRequiresActualIpv6InsteadOfIpv4OrRegName(string $host): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('invalid_target');
        TargetNormalizer::host($host);
    }

    public static function invalidBracketedHosts(): iterable
    {
        yield 'IPv4' => ['[127.0.0.1]'];
        yield 'hexadecimal-looking reg-name' => ['[face]'];
        yield 'DNS reg-name' => ['[api.example]'];
    }

    #[DataProvider('inconsistentUriFields')]
    public function testPsrUriFieldsCannotHideForbiddenAuthorityData(
        string $method,
        string $value,
        string $reason,
    ): void {
        $uri = $this->createMock(\Psr\Http\Message\UriInterface::class);
        $uri->method('__toString')->willReturn('https://api.example');
        $uri->method('getUserInfo')->willReturn($method === 'getUserInfo' ? $value : '');
        $uri->method('getFragment')->willReturn($method === 'getFragment' ? $value : '');
        $uri->method('getScheme')->willReturn($method === 'getScheme' ? $value : 'https');
        $uri->method('getHost')->willReturn('api.example');
        $uri->method('getPort')->willReturn($method === 'getPort' ? (int) $value : null);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeader')->willReturn(['api.example']);
        $request->expects(self::never())->method('withUri');
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage($reason);
        (new TargetNormalizer())->normalize($request);
    }

    public static function inconsistentUriFields(): iterable
    {
        yield 'hidden credentials' => ['getUserInfo', 'user', 'invalid_target'];
        yield 'hidden fragment' => ['getFragment', 'fragment', 'invalid_target'];
        yield 'hidden scheme' => ['getScheme', 'ftp', 'scheme_forbidden'];
        yield 'hidden lower port' => ['getPort', '0', 'invalid_target'];
        yield 'hidden upper port' => ['getPort', '65536', 'invalid_target'];
    }
}
