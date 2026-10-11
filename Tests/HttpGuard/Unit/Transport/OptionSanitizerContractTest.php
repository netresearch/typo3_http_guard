<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Transport\OptionSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class OptionSanitizerContractTest extends TestCase
{
    private array $savedProxies = [];

    protected function setUp(): void
    {
        foreach (getenv(null, true) as $name => $value) {
            if (in_array(strtolower((string) $name), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                $this->savedProxies[$name] = $value;
                putenv((string) $name);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (getenv(null, true) as $name => $value) {
            if (in_array(strtolower((string) $name), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                putenv((string) $name);
            }
        }
        foreach ($this->savedProxies as $name => $value) {
            putenv($name . '=' . $value);
        }
    }

    #[DataProvider('validLeafOptions')]
    public function testPreservesEachAllowedLeafOption(array $input): void
    {
        $expected              = $input;
        $expected['proxy']     = '';
        $expected['protocols'] = $input['protocols'] ?? ['http', 'https'];
        self::assertSame($expected, (new OptionSanitizer())->sanitize(new Request('GET', 'https://guard.test'), $input));
    }

    public static function validLeafOptions(): iterable
    {
        yield 'defaults' => [[]];
        foreach (['timeout', 'connect_timeout'] as $name) {
            foreach ([0, 0.0, 0.125, 1, 12.5] as $value) {
                yield $name . ':' . get_debug_type($value) . ':' . $value => [[$name => $value]];
            }
        }
        foreach ([false, true, 'gzip'] as $value) {
            yield 'decoding:' . get_debug_type($value) . ':' . (string) $value => [['decode_content' => $value]];
        }
        foreach ([false, true, 0, 0.0, 0.5, 100] as $value) {
            yield 'expect:' . get_debug_type($value) . ':' . (string) $value => [['expect' => $value]];
        }
        foreach ([true, false, __FILE__] as $value) {
            yield 'verification:' . get_debug_type($value) . ':' . (string) $value => [['verify' => $value]];
        }
        foreach (['cert', 'ssl_key'] as $name) {
            yield $name . ':path' => [[$name => __FILE__]];
            yield $name . ':path-and-passphrase' => [[$name => [__FILE__, 'synthetic-passphrase']]];
        }
        foreach (['cert_type', 'ssl_key_type'] as $name) {
            foreach (['PEM', 'DER', 'P12'] as $value) {
                yield $name . ':' . $value => [[$name => $value]];
            }
        }
        foreach (['v4', 'v6'] as $value) {
            yield 'resolution:' . $value => [['force_ip_resolve' => $value]];
        }
        foreach (['none', 'eager', 'wait', 'require_eager', 'require_wait'] as $value) {
            yield 'multiplex:' . $value => [['multiplex' => $value]];
        }
        foreach ([['http'], ['https'], ['https', 'http']] as $value) {
            yield 'protocols:' . implode(',', $value) => [['protocols' => $value]];
        }
        foreach (['on_headers', 'on_stats', 'on_trailers', 'progress'] as $name) {
            yield $name . ':callable' => [
                [
                    $name => static function (): void {
                        /* Controlled callback identity. */
                    },
                ],
            ];
        }
        foreach (['request_factory', 'response_factory', 'stream_factory', 'uri_factory'] as $name) {
            yield $name . ':PSR-interface' => [[$name => new HttpFactory()]];
        }
        yield 'string sink' => [['sink' => '/synthetic/output']];
        yield 'PSR stream sink' => [['sink' => Utils::streamFor('synthetic body')]];
    }

    #[DataProvider('invalidOptions')]
    public function testMalformedOptionIsDeniedBeforeLeafConstruction(array $input, string $reason): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage($reason);
        (new OptionSanitizer())->sanitize(new Request('GET', 'https://guard.test'), $input);
    }

    public static function invalidOptions(): iterable
    {
        foreach (['timeout', 'connect_timeout', 'expect'] as $name) {
            foreach ([-1, -0.125, INF, -INF, NAN, '1', [], new stdClass()] as $i => $value) {
                yield $name . ':bad:' . $i => [[$name => $value], 'option_forbidden'];
            }
        }
        foreach (['synchronous', 'http_errors'] as $name) {
            foreach ([0, 'false', [], null] as $i => $value) {
                yield $name . ':bad:' . $i => [[$name => $value], 'option_forbidden'];
            }
        }
        foreach (['on_headers', 'on_stats', 'on_trailers', 'progress'] as $name) {
            yield $name . ':noncallable' => [[$name => new stdClass()], 'option_forbidden'];
        }
        foreach (['cert', 'ssl_key'] as $name) {
            foreach ([
                false,
                null,
                new stdClass(),
                [],
                [__FILE__],
                [__FILE__, 'x', 'y'],
                [0, 'x'],
                [__FILE__, 0],
                [1 => __FILE__, 2 => 'x'],
                '/nonexistent/guard-fixture',
            ] as $i => $value) {
                yield $name . ':bad:' . $i => [[$name => $value], 'option_forbidden'];
            }
        }
        foreach ([
            'cert_type',
            'ssl_key_type',
            'force_ip_resolve',
            'multiplex',
            'decode_content',
            'sink',
            'request_factory',
            'response_factory',
            'stream_factory',
            'uri_factory',
        ] as $name) {
            yield $name . ':object' => [[$name => new stdClass()], 'option_forbidden'];
        }
        foreach ([null, 0, [], new stdClass(), '/nonexistent/guard-fixture'] as $i => $value) {
            yield 'verify:bad:' . $i => [['verify' => $value], 'option_forbidden'];
        }
        foreach ([true, 0, [], new stdClass()] as $i => $value) {
            yield 'cookies:bad:' . $i => [['cookies' => $value], 'option_forbidden'];
        }
        foreach ([false, [], ['ftp'], ['https', 'ftp'], [1 => 'https'], [new stdClass()], ['HTTPS'], [1]] as $i => $value) {
            yield 'protocols:bad:' . $i => [['protocols' => $value], 'option_forbidden'];
        }
        foreach (['stream', 'debug', 'idn_conversion'] as $name) {
            foreach ([true, 0, 'false', []] as $i => $value) {
                yield $name . ':bad:' . $i => [[$name => $value], 'option_forbidden'];
            }
        }
        foreach ([-1, 0.25, '0', []] as $i => $value) {
            yield 'delay:bad:' . $i => [['delay' => $value], 'option_forbidden'];
        }
        foreach (['__redirect_count' => 5, '__guzzle_digest_retries' => 2, 'retries' => 1000] as $name => $max) {
            foreach ([-1, $max + 1, 0.0, '0'] as $i => $value) {
                yield $name . ':bad:' . $i => [[$name => $value], 'option_forbidden'];
            }
        }
        foreach ([false, 0, 'http://synthetic-proxy:8080', ['http' => 'synthetic']] as $i => $value) {
            yield 'proxy:bad:' . $i => [['proxy' => $value], 'proxy_unsupported'];
        }
        yield 'numeric option key' => [[0 => true], 'option_forbidden'];
        yield 'untrusted handler without expected identity' => [['handler' => HandlerStack::create()], 'option_forbidden'];
        yield 'explicit null handler is not absence' => [['handler' => null], 'option_forbidden'];
        foreach (['3', '3.0', 3, 1.0, false, [], new stdClass()] as $i => $value) {
            yield 'version:bad:' . $i => [['version' => $value], 'option_forbidden'];
        }
        foreach ([
            true,
            'basic',
            [],
            ['user'],
            ['user', 'pass', 'basic', 'extra'],
            [0, 'pass'],
            ['user', 0],
            ['user', 'pass', 'kerberos'],
            [1 => 'user', 2 => 'pass'],
        ] as $i => $value) {
            yield 'auth:bad:' . $i => [['auth' => $value], 'option_forbidden'];
        }
    }

    #[DataProvider('validatedControls')]
    public function testValidatedHigherLevelControlsNeverReachTheLeaf(array $input): void
    {
        self::assertSame(
            ['proxy' => '', 'protocols' => ['http', 'https']],
            (new OptionSanitizer())->sanitize(new Request('GET', 'https://guard.test'), $input),
        );
    }

    public static function validatedControls(): iterable
    {
        foreach (['synchronous', 'http_errors'] as $name) {
            yield $name . ':false' => [[$name => false]];
            yield $name . ':true' => [[$name => true]];
        }
        foreach (['__redirect_count' => 5, '__guzzle_digest_retries' => 2, 'retries' => 1000] as $name => $max) {
            foreach ([0, 1, $max] as $value) {
                yield $name . ':' . $value => [[$name => $value]];
            }
        }
        foreach (['1.0', '1.1', '2', '2.0', 2, 1.1, 2.0] as $i => $value) {
            yield 'version:' . $i => [['version' => $value]];
        }
        foreach (['', null, []] as $i => $value) {
            yield 'direct proxy:' . $i => [['proxy' => $value]];
        }
        yield 'zero scheduling' => [['stream' => false, 'debug' => false, 'idn_conversion' => false, 'delay' => 0.0]];
        yield 'cookie jar' => [['cookies' => new CookieJar()]];
        yield 'higher layer basic authentication' => [['auth' => ['synthetic-user', 'synthetic-pass', 'basic']]];
        yield 'unspecified authentication mode' => [['auth' => ['synthetic-user', 'synthetic-pass']]];
        yield 'base URI cannot reroute a resolved request' => [['base_uri' => 'https://ignored.test']];
    }

    #[DataProvider('redirectSettings')]
    public function testRedirectSettingsRespectThePolicyLimit(mixed $settings, bool $allowed): void
    {
        if (!$allowed) {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('redirect_forbidden');
        }
        (new OptionSanitizer())->assertRedirects($settings);
        if ($allowed) {
            self::assertTrue($allowed);
        }
    }

    public static function redirectSettings(): iterable
    {
        yield 'disabled' => [false, true];
        yield 'default' => [true, true];
        yield 'zero limit' => [['max' => 0], true];
        yield 'exact limit' => [['max' => 5], true];
        yield 'over limit' => [['max' => 6], false];
        yield 'negative limit' => [['max' => -1], false];
        yield 'float limit' => [['max' => 0.0], false];
        yield 'string limit' => [['max' => '5'], false];
        yield 'invalid setting type' => ['true', false];
        yield 'unknown setting' => [['unknown' => false], false];
        foreach (['strict', 'referer', 'track_redirects'] as $name) {
            yield $name . ':valid' => [[$name => false], true];
            yield $name . ':invalid' => [[$name => 0], false];
        }
        yield 'redirect callback' => [
            [
                'on_redirect' => static function (): void {
                    /* Synthetic identity callback. */
                },
            ],
            true,
        ];
        yield 'invalid redirect callback' => [['on_redirect' => 0], false];
        yield 'web redirects' => [['protocols' => ['http', 'https']], true];
        yield 'non-web redirect' => [['protocols' => ['https', 'ftp']], false];
        yield 'non-list redirect protocols' => [['protocols' => [1 => 'https']], false];
    }

    public function testExpectedHandlerCannotDisappear(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('option_forbidden');
        (new OptionSanitizer())->sanitize(new Request('GET', 'https://guard.test'), [], HandlerStack::create());
    }

    public function testRequiredVerificationPreservesTrueAndReadableCertificate(): void
    {
        foreach ([true, __FILE__] as $value) {
            self::assertSame(
                $value,
                (new OptionSanitizer(requireVerification: true))->sanitize(
                    new Request('GET', 'https://guard.test'),
                    ['verify' => $value],
                )['verify'],
            );
        }
    }

    public function testAResourceSinkRetainsItsIdentity(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        try {
            self::assertSame(
                $stream,
                (new OptionSanitizer())->sanitize(new Request('GET', 'https://guard.test'), ['sink' => $stream])['sink'],
            );
        } finally {
            fclose($stream);
        }
    }

    public function testAbsentRedirectsDoNotRequestTheSdkDefaultFiveHops(): void
    {
        self::assertSame(
            ['proxy' => '', 'protocols' => ['http', 'https']],
            (new OptionSanitizer(maxRedirects: 0))->sanitize(new Request('GET', 'https://guard.test')),
        );
    }

    public function testRequiredTlsVerificationDoesNotNeedAnExplicitCallerOverride(): void
    {
        self::assertSame(
            ['proxy' => '', 'protocols' => ['http', 'https']],
            (new OptionSanitizer(requireVerification: true))->sanitize(new Request('GET', 'https://guard.test')),
        );
    }

    public function testEnablingSdkDefaultRedirectsExceedsASmallerPolicyLimit(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('redirect_forbidden');
        (new OptionSanitizer(maxRedirects: 4))->assertRedirects(true);
    }

    public function testProcessProxyInventoryIncludesEveryNonemptyNameWithoutValues(): void
    {
        putenv('HTTP_PROXY=synthetic-secret-one');
        putenv('https_proxy=synthetic-secret-two');
        putenv('NO_PROXY=');
        self::assertEqualsCanonicalizing(['HTTP_PROXY', 'https_proxy'], OptionSanitizer::processProxyNames());
    }
}
