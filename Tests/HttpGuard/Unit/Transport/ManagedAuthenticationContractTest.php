<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Transport\OptionSanitizer;
use Netresearch\HttpGuard\Transport\RuntimeSupport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/** A unit-only version discriminator; this is not an SDK ABI or wire qualification. */
interface ManagedAuthSdkMajor
{
    public const MAJOR_VERSION = 7;
}

final class ManagedAuthenticationContractTest extends TestCase
{
    #[DataProvider('validNativeAuthentication')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSdkSevenManagedAuthenticationPreservesOnlyExactNativeCredentials(
        array $auth,
        array $raw,
    ): void {
        $this->installUnitVersionDiscriminator();
        $this->withoutProxy(
            function () use ($auth, $raw): void {
                self::assertSame(
                    ['proxy' => '', 'protocols' => ['http', 'https'], 'curl' => $raw],
                    (new OptionSanitizer())->sanitize(
                        new Request('GET', 'https://synthetic.example'),
                        ['auth' => $auth, 'curl' => $raw],
                    ),
                );
            },
        );
    }

    public static function validNativeAuthentication(): iterable
    {
        foreach (['digest' => CURLAUTH_DIGEST, 'ntlm' => CURLAUTH_NTLM] as $mode => $constant) {
            foreach ([['synthetic-user', 'synthetic-password'], ['', ''], ['user:part', 'password:part']] as $index => [$username, $password]) {
                yield $mode . ':' . $index => [
                    [$username, $password, $mode],
                    [CURLOPT_HTTPAUTH => $constant, CURLOPT_USERPWD => $username . ':' . $password],
                ];
            }
        }
    }

    #[DataProvider('invalidNativeAuthentication')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCallerCurlOptionsCannotPretendToBeSdkManagedAuthentication(array $options): void
    {
        $this->installUnitVersionDiscriminator();
        $this->withoutProxy(
            function () use ($options): void {
                $this->expectException(PolicyException::class);
                $this->expectExceptionMessage('option_forbidden');
                (new OptionSanitizer())->sanitize(new Request('GET', 'https://synthetic.example'), $options);
            },
        );
    }

    public static function invalidNativeAuthentication(): iterable
    {
        $auth = ['synthetic-user', 'synthetic-password', 'digest'];
        $raw  = [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => 'synthetic-user:synthetic-password'];
        foreach ([
            null,
            false,
            'synthetic',
            [],
            ['synthetic-user', 'synthetic-password'],
            ['synthetic-user', 'synthetic-password', 'basic'],
        ] as $index => $badAuth) {
            yield 'unsupported-auth:' . $index => [['auth' => $badAuth, 'curl' => $raw]];
        }
        foreach ([
            null,
            false,
            'synthetic',
            [],
            [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST],
            [CURLOPT_USERPWD => 'synthetic-user:synthetic-password'],
            $raw + [CURLOPT_PORT => 443],
            [CURLOPT_HTTPAUTH => CURLAUTH_NTLM, CURLOPT_USERPWD => 'synthetic-user:synthetic-password'],
            [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => 'different:synthetic-password'],
            [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => 'synthetic-user:different'],
        ] as $index => $badRaw) {
            yield 'unmanaged-native-options:' . $index => [['auth' => $auth, 'curl' => $badRaw]];
        }
    }

    private function installUnitVersionDiscriminator(): void
    {
        self::assertFalse(
            interface_exists('GuzzleHttp\ClientInterface', false),
            'The isolated unit process must install its discriminator before SDK loading',
        );
        self::assertTrue(class_alias(ManagedAuthSdkMajor::class, 'GuzzleHttp\ClientInterface'));
        self::assertSame(7, RuntimeSupport::major());
    }

    private function withoutProxy(callable $run): void
    {
        $saved = [];
        foreach (getenv(null, true) as $name => $value) {
            if (in_array(strtolower((string) $name), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                $saved[$name] = $value;
                putenv((string) $name);
            }
        }
        try {
            $run();
        } finally {
            foreach ($saved as $name => $value) {
                putenv($name . '=' . $value);
            }
        }
    }
}
