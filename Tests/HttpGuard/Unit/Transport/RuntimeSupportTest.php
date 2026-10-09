<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Transport\RuntimeSupport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
final class RuntimeSupportTest extends TestCase
{
    public function testCurrentQualifiedWholeTupleIsSupported(): void
    {
        RuntimeSupport::assertSupported();
        self::assertContains(RuntimeSupport::major(), [7, 8]);
    }
    #[DataProvider('mixedDependencies')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testMixingOneKnownDependencyPatchIsRejected(
        string $package,
        string $first,
        string $second
    ): void
    {
        $actualMajor = RuntimeSupport::major();
        $data = InstalledVersions::getAllRawData()[0];
        $current = ltrim((string) InstalledVersions::getPrettyVersion($package), 'v');
        $replacement = $current === $first ? $second : $first;
        $data['versions'][$package]['pretty_version'] = $replacement;
        $data['versions'][$package]['version'] = $replacement . '.0';
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->unregister();
            spl_autoload_register([$loader, 'loadClass'], true, true);
        }
        InstalledVersions::reload($data);
        self::assertSame(
            $replacement,
            InstalledVersions::getPrettyVersion($package)
        );
        self::assertSame($actualMajor, RuntimeSupport::major());
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('transport_unsupported');
        RuntimeSupport::assertSupported();
    }
    public static function mixedDependencies(): iterable
    {
        yield 'guzzle' => ['guzzlehttp/guzzle', '7.15.3', '7.15.5'];
        yield 'promises' => ['guzzlehttp/promises', '2.5.2', '2.5.3'];
        yield 'psr7' => ['guzzlehttp/psr7', '2.13.0', '2.13.1'];
    }
}
