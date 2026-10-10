<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use Composer\InstalledVersions;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures\MultiContractDestructorOnly;
use Netresearch\HttpGuard\Transport\RuntimeCapabilityProbe;
use Netresearch\HttpGuard\Transport\RuntimeSupport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class RuntimeCapabilityContractTest extends TestCase
{
    /** Unit-level missing-capability boundaries; no real SDK tuple or wire claim. */
    #[DataProvider('capabilityResults')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testMissingOrUnsupportedCapabilitiesDenyBeforeNativeConstruction(
        array $overrides,
        bool $supported,
    ): void {
        require_once __DIR__ . '/Fixtures/RuntimeCapabilities.php';
        RuntimeCapabilityProbe::$overrides = $overrides;
        if (!$supported) {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('transport_unsupported');
        }
        RuntimeSupport::assertSupported();
        if ($supported) {
            self::assertContains(RuntimeSupport::major(), [7, 8]);
        }
    }

    public static function capabilityResults(): iterable
    {
        yield 'ordinary native capabilities' => [[], true];
        yield 'curl extension unavailable' => [['extension:curl' => false], false];
        yield 'multi function unavailable' => [['function:curl_multi_exec' => false], false];
        foreach (['CURLOPT_RESOLVE', 'CURLOPT_FRESH_CONNECT', 'CURLOPT_FORBID_REUSE'] as $name) {
            yield 'missing ' . $name => [['defined:' . $name => false], false];
        }
        yield 'Composer installation metadata unavailable' => [['class:Composer\InstalledVersions' => false], false];
        yield 'curl metadata unavailable' => [['curl_version' => false], false];
        yield 'curl version number omitted' => [['curl_version' => []], false];
        yield 'curl immediately below floor' => [['curl_version' => ['version_number' => 0x73AFF]], false];
        yield 'curl exactly at floor' => [['curl_version' => ['version_number' => 0x73B00]], true];
        yield 'SDK major discriminator unavailable' => [['defined:GuzzleHttp\ClientInterface::MAJOR_VERSION' => false], false];
        foreach ([6, 9] as $major) {
            yield 'unqualified SDK major ' . $major => [['constant:GuzzleHttp\ClientInterface::MAJOR_VERSION' => $major], false];
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSevenFloorDiscriminatorUsesItsOwnCompatibleMetadataAndCleanupContract(): void
    {
        require_once __DIR__ . '/Fixtures/RuntimeCapabilities.php';
        require_once __DIR__ . '/Fixtures/RuntimeContractShapes.php';
        self::assertFalse(class_exists('GuzzleHttp\Handler\CurlMultiHandler', false));
        self::assertTrue(class_alias(MultiContractDestructorOnly::class, 'GuzzleHttp\Handler\CurlMultiHandler'));
        RuntimeCapabilityProbe::$overrides = ['constant:GuzzleHttp\ClientInterface::MAJOR_VERSION' => 7];
        $data                              = InstalledVersions::getAllRawData()[0];
        foreach (['guzzlehttp/guzzle' => '7.15.2', 'guzzlehttp/promises' => '2.5.1', 'guzzlehttp/psr7' => '2.13.0'] as $package => $version) {
            $data['versions'][$package]['pretty_version'] = $version;
            $data['versions'][$package]['version']        = $version . '.0';
        }
        $autoloaders = spl_autoload_functions();
        $loaders     = \Composer\Autoload\ClassLoader::getRegisteredLoaders();
        InstalledVersions::reload($data);
        // Isolated metadata fixture: retain source/mutant loader priority and replace duplicate vendor metadata directly.
        $cache = new ReflectionProperty(InstalledVersions::class, 'installedByVendor');
        $cache->setValue(null, array_fill_keys(array_keys($loaders), $data));
        self::assertSame($autoloaders, spl_autoload_functions());
        self::assertSame($loaders, \Composer\Autoload\ClassLoader::getRegisteredLoaders());
        RuntimeSupport::assertSupported();
        self::assertSame(7, RuntimeSupport::major());
    }
}
