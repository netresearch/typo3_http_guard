<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Transport\RuntimeSupport;
use PHPUnit\Framework\Attributes\{DataProvider, PreserveGlobalState, RunTestsInSeparateProcesses};
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class RuntimeMutationBoundaryTest extends TestCase
{
    #[DataProvider('spellings')]
    public function testVersionGrammarIsCheckedBeforeAcceptingACompatibleVersion(
        string $decoration,
        bool $supported,
    ): void {
        $version = RuntimeSupport::major() . '.99.99';
        $version = match ($decoration) {
            'prefix'  => '99x' . $version,
            'suffix'  => $version . '@unreviewed',
            'newline' => $version . "\n",
            default   => $version,
        };
        $data                                                    = InstalledVersions::getAllRawData()[0];
        $data['versions']['guzzlehttp/guzzle']['pretty_version'] = $version;
        $loaders                                                 = ClassLoader::getRegisteredLoaders();
        $autoloaders                                             = spl_autoload_functions();
        InstalledVersions::reload($data);
        (new ReflectionProperty(InstalledVersions::class, 'installedByVendor'))->setValue(
            null,
            array_fill_keys(array_keys($loaders), $data),
        );
        self::assertSame($autoloaders, spl_autoload_functions());
        if (!$supported) {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('transport_unsupported');
        }
        RuntimeSupport::assertSupported();
        if ($supported) {
            self::assertContains(RuntimeSupport::major(), [7, 8]);
        }
    }

    public static function spellings(): iterable
    {
        yield 'ordinary compatible version' => ['plain', true];
        yield 'numeric prefix' => ['prefix', false];
        yield 'invalid suffix' => ['suffix', false];
        yield 'terminal newline' => ['newline', false];
    }

    public function testWrongNamedFactoryParameterFailsInTheMutantAwarePhpunitProcess(): void
    {
        require_once __DIR__ . '/Fixtures/RuntimeWrongNamedFactory.php';
        self::assertFalse(interface_exists(\GuzzleHttp\Handler\CurlFactoryInterface::class, false));
        self::assertTrue(
            class_alias(Fixtures\RuntimeWrongNamedFactory::class, \GuzzleHttp\Handler\CurlFactoryInterface::class),
        );
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('transport_unsupported');
        RuntimeSupport::assertSupported();
    }
}
