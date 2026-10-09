<?php

declare (strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Transport\OptionSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OptionSanitizerTest extends TestCase
{
    private array $proxyEnvironment = [];

    protected function setUp(): void
    {
        foreach (getenv(null, true) as $key => $value) {
            if (in_array(
                strtolower((string) $key),
                ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'],
                true
            )) {
                $this->proxyEnvironment[$key] = $value;
                putenv($key);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (getenv(null, true) as $key => $value) {
            if (in_array(
                strtolower((string) $key),
                ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'],
                true
            )) {
                putenv($key);
            }
        }
        foreach ($this->proxyEnvironment as $key => $value) {
            putenv($key . '=' . $value);
        }
    }

    public function testPreservesZeroTimeoutsAndComposedCallbacks(): void
    {
        $headers = static function (): void {
        };
        $stats = static function (): void {
        };
        $options = (new OptionSanitizer(5, false))->sanitize(
            new Request('GET', 'https://guard.test'),
            [
                'timeout' => 0,
                'connect_timeout' => 0,
                'verify' => false,
                'on_headers' => $headers,
                'on_stats' => $stats,
                'idn_conversion' => false,
                'decode_content' => true,
                'allow_redirects' => ['max' => 3, 'protocols' => ['https'], 'strict' => false],
                'http_errors' => false,
                'cookies' => false,
            ]
        );
        self::assertSame(0, $options['timeout']);
        self::assertSame(0, $options['connect_timeout']);
        self::assertFalse($options['verify']);
        self::assertSame($headers, $options['on_headers']);
        self::assertSame($stats, $options['on_stats']);
        self::assertArrayNotHasKey('allow_redirects', $options);
        self::assertSame('', $options['proxy']);
    }

    #[DataProvider('forbiddenOptions')]
    public function testRejectsUncontrolledOptions(
        array $options,
        string $reason
    ): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage($reason);
        (new OptionSanitizer(5, false))->sanitize(
            new Request('GET', 'https://guard.test'),
            $options
        );
    }

    public static function forbiddenOptions(): iterable
    {
        foreach ([
            CURLOPT_RESOLVE,
            CURLOPT_CONNECT_TO,
            CURLOPT_URL,
            CURLOPT_SHARE,
            CURLOPT_UNIX_SOCKET_PATH,
            CURLOPT_FOLLOWLOCATION,
        ] as $key) {
            yield 'raw_' . $key => [['curl' => [$key => 'synthetic']], 'option_forbidden'];
        }
        foreach ([
            ['stream' => true],
            ['delay' => 1],
            ['read_timeout' => 0],
            ['curl_multi' => []],
            ['stream_context' => []],
            ['transport_sharing' => 'none'],
            ['debug' => true],
            ['unknown' => false],
            ['_curl_retries' => 2],
            ['idn_conversion' => true],
            ['version' => '3.0'],
            ['timeout' => INF],
            ['connect_timeout' => -1],
            ['on_stats' => 'missing_callback'],
            ['force_ip_resolve' => 'prefer-v4'],
            ['allow_redirects' => ['max' => 6]],
            ['allow_redirects' => ['protocols' => ['ftp']]],
            ['allow_redirects' => ['unknown' => true]],
        ] as $i => $options) {
            yield 'option_' . $i => [
                $options,
                isset($options['allow_redirects']) ? 'redirect_forbidden' : 'option_forbidden',
            ];
        }
        yield 'explicit_proxy' => [['proxy' => 'http://synthetic-proxy:8080'], 'proxy_unsupported'];
        yield 'no_proxy_guess' => [
            [
                'proxy' => [
                    'http' => 'http://synthetic-proxy:8080',
                    'no' => ['guard.test'],
                ],
            ],
            'proxy_unsupported',
        ];
    }

    public function testKnownStackIdentityIsRequiredAndRemoved(): void
    {
        $stack = HandlerStack::create();
        $options = (new OptionSanitizer(5, false))->sanitize(
            new Request('GET', 'https://guard.test'),
            ['handler' => $stack],
            $stack
        );
        self::assertArrayNotHasKey('handler', $options);
        $this->expectException(PolicyException::class);
        (new OptionSanitizer(5, false))->sanitize(
            new Request('GET', 'https://guard.test'),
            ['handler' => HandlerStack::create()],
            $stack
        );
    }

    public function testProcessProxyIsRejectedEvenWhenCallerRequestsDirect(): void
    {
        putenv('hTtP_pRoXy=http://synthetic-proxy:8080');
        putenv('NO_PROXY=*');
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('proxy_unsupported');
        (new OptionSanitizer(5, false))->sanitize(
            new Request('GET', 'https://guard.test'),
            ['proxy' => '']
        );
    }

    public function testIncomingProxyHeaderDoesNotBecomeProcessConfiguration(): void
    {
        $saved = $_SERVER['HTTP_PROXY'] ?? null;
        $_SERVER['HTTP_PROXY'] = 'http://incoming-header:8080';
        try {
            self::assertSame(
                '',
                (new OptionSanitizer(5, false))->sanitize(
                    new Request('GET', 'https://guard.test')
                )['proxy']
            );
        } finally {
            if ($saved === null) {
                unset($_SERVER['HTTP_PROXY']);
            } else {
                $_SERVER['HTTP_PROXY'] = $saved;
            }
        }
    }

    public function testPolicyForbidsDisablingTlsVerification(): void
    {
        $this->expectException(PolicyException::class);
        (new OptionSanitizer(5, true))->sanitize(
            new Request('GET', 'https://guard.test'),
            ['verify' => false]
        );
    }
    public function testMalformedLeafTypesHaveStablePolicyErrors(): void
    {
        foreach ([
            ['protocols' => [new \stdClass()]],
            ['allow_redirects' => ['protocols' => [new \stdClass()]]],
            ['version' => new \stdClass()],
            ['verify' => null],
        ] as $options) {
            try {
                (new OptionSanitizer())->sanitize(
                    new Request('GET', 'https://guard.test'),
                    $options
                );
                self::fail('Malformed option accepted');
            } catch (PolicyException $error) {
                self::assertContains(
                    $error->reasonCode(),
                    ['option_forbidden', 'redirect_forbidden']
                );
            }
        }
    }
}
