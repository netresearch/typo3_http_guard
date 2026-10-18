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

final class ResidualTargetContractTest extends TestCase
{
    public function testNormalizationAcceptsTheExactRawUriLengthLimit(): void
    {
        $prefix = 'https://api.example/';
        $raw    = $prefix . str_repeat('p', 8192 - strlen($prefix));
        self::assertSame(8192, strlen($raw));
        $target = (new TargetNormalizer())->normalize(new Request('GET', $raw));
        self::assertSame($raw, (string) $target->canonicalRequest->getUri());
    }

    public function testHostHeaderCannotHideTrailingDataAfterAnOtherwiseMatchingPort(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('authority_mismatch');
        (new TargetNormalizer())->normalize(
            new Request('GET', 'https://api.example', ['Host' => 'api.example:443:trailing']),
        );
    }

    #[DataProvider('numericLabels')]
    public function testNumericDnsLabelsRemainNamesInsteadOfBecomingAddressLiterals(string $host): void
    {
        $target = (new TargetNormalizer())->normalize(new Request('GET', 'https://' . $host));
        self::assertSame($host, $target->host);
        self::assertNull($target->literalIp);
    }

    /** @return iterable<string,array{string}> */
    public static function numericLabels(): iterable
    {
        yield 'numeric suffix' => ['api.123'];
        yield 'numeric prefix' => ['123.api'];
    }

    #[DataProvider('nonDefaultPorts')]
    public function testCanonicalRequestRetainsPortsThatAreNotDefaultForItsScheme(string $scheme, int $port): void
    {
        $uri    = $scheme . '://api.example:' . $port;
        $target = (new TargetNormalizer())->normalize(new Request('GET', $uri));
        self::assertSame($port, $target->port);
        self::assertSame($port, $target->canonicalRequest->getUri()->getPort());
        self::assertSame($uri, (string) $target->canonicalRequest->getUri());
        self::assertSame('api.example:' . $port, $target->canonicalRequest->getHeaderLine('Host'));
    }

    /** @return iterable<string,array{string,int}> */
    public static function nonDefaultPorts(): iterable
    {
        yield 'HTTP uses HTTPS port' => ['http', 443];
        yield 'HTTPS uses HTTP port' => ['https', 80];
        yield 'below HTTPS default' => ['https', 442];
        yield 'above HTTPS default' => ['https', 444];
        yield 'below HTTP default' => ['http', 79];
        yield 'above HTTP default' => ['http', 81];
    }

    public function testUriObjectCannotHideMalformedRawAuthorityFromNormalization(): void
    {
        $uri = new class ('https://api.example') extends Uri {
            public function __toString(): string
            {
                return 'https:api.example';
            }
        };
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('invalid_target');
        (new TargetNormalizer())->normalize(new Request('GET', $uri));
    }

    public function testRawUriRequiresAuthorityImmediatelyAfterItsScheme(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('invalid_target');
        (new TargetNormalizer())->assertRawUri('https:api.example');
    }

    #[DataProvider('malformedIpv6Hosts')]
    public function testMalformedIpv6ReturnsTheBoundedPolicyFailure(string $host): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('invalid_target');
        TargetNormalizer::host($host);
    }

    /** @return iterable<string,array{string}> */
    public static function malformedIpv6Hosts(): iterable
    {
        yield 'unbracketed' => ['2001:db8:::1'];
        yield 'bracketed' => ['[2001:db8:::1]'];
        yield 'embedded NUL' => ["2001:db8::\x00"];
    }

    public function testPsrRequestHostHeaderCannotHideATrailingLineFeed(): void
    {
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $request->method('getUri')->willReturn(new Uri('https://api.example'));
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeader')->willReturn(["api.example:443\n"]);
        $request->expects(self::never())->method('withUri');
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('authority_mismatch');
        (new TargetNormalizer())->normalize($request);
    }
}
