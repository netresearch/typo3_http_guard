<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Transport;

use Composer\InstalledVersions;
use GuzzleHttp\ClientInterface;
use Netresearch\HttpGuard\PolicyException;
final class RuntimeSupport
{
    public static function assertSupported(): void
    {
        if (!extension_loaded('curl') || !function_exists('curl_multi_exec') || !defined('CURLOPT_RESOLVE') || !defined('CURLOPT_FRESH_CONNECT') || !defined('CURLOPT_FORBID_REUSE') || !class_exists(InstalledVersions::class)) {
            throw new PolicyException('transport_unsupported');
        }
        $curl = curl_version();
        if (($curl['version_number'] ?? 0) < 0x73b00) {
            throw new PolicyException('transport_unsupported');
        }
        $expected = self::major() === 7 ? [
            'guzzlehttp/guzzle' => '7.15.5',
            'guzzlehttp/promises' => '2.5.3',
            'guzzlehttp/psr7' => '2.13.1',
        ] : [
            'guzzlehttp/guzzle' => '8.2.0',
            'guzzlehttp/promises' => '3.0.2',
            'guzzlehttp/psr7' => '3.1.0',
        ];
        if (!in_array(self::major(), [7, 8], true)) {
            throw new PolicyException('transport_unsupported');
        }
        foreach ($expected as $package => $version) {
            if (!InstalledVersions::isInstalled($package) || ltrim((string) InstalledVersions::getPrettyVersion($package), 'v') !== $version) {
                throw new PolicyException('transport_unsupported');
            }
        }
    }
    public static function major(): int
    {
        return (int) constant("GuzzleHttp\\ClientInterface::MAJOR_VERSION");
    }
}
