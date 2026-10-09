<?php

declare(strict_types=1);

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
    public function testCurrentInstalledCompatibleGraphIsSupported(): void
    {
        RuntimeSupport::assertSupported();
        self::assertContains(RuntimeSupport::major(), [7, 8]);
    }

    /** @param array<string, string|null> $versions */
    #[DataProvider('dependencyVersions')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSemanticDependencyCompatibility(array $versions, bool $supported): void
    {
        $actualMajor = RuntimeSupport::major();
        $data        = InstalledVersions::getAllRawData()[0];
        foreach ($versions as $package => $replacement) {
            if ($replacement === null) {
                unset($data['versions'][$package]);
                continue;
            }
            $data['versions'][$package]['pretty_version'] = $replacement;
            $data['versions'][$package]['version']        = ltrim($replacement, 'v') . '.0';
        }
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->unregister();
            spl_autoload_register([$loader, 'loadClass'], true, true);
        }
        InstalledVersions::reload($data);
        self::assertSame($actualMajor, RuntimeSupport::major());
        if (!$supported) {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('transport_unsupported');
        }
        RuntimeSupport::assertSupported();
        if ($supported) {
            self::assertSame($actualMajor, RuntimeSupport::major());
        }
    }

    /** @return iterable<string, array{array<string, string|null>, bool}> */
    public static function dependencyVersions(): iterable
    {
        $seven = RuntimeSupport::major() === 7;
        yield 'independent Guzzle patch' => [['guzzlehttp/guzzle' => $seven ? '7.15.6' : '8.2.1'], true];
        yield 'independent Promises patch' => [['guzzlehttp/promises' => $seven ? '2.5.4' : '3.0.3'], true];
        yield 'independent PSR7 patch' => [['guzzlehttp/psr7' => $seven ? '2.13.2' : '3.1.1'], true];
        yield 'later compatible minor versions' => [
            [
                'guzzlehttp/guzzle'   => $seven ? '7.16.0' : '8.3.0',
                'guzzlehttp/promises' => $seven ? '2.6.0' : '3.1.0',
                'guzzlehttp/psr7'     => $seven ? '2.14.0' : '3.2.0',
            ],
            true,
        ];
        yield 'safe dependency floors' => [
            [
                'guzzlehttp/guzzle'   => $seven ? '7.15.2' : '8.2.0',
                'guzzlehttp/promises' => $seven ? '2.5.1' : '3.0.2',
                'guzzlehttp/psr7'     => $seven ? '2.13.0' : '3.1.0',
            ],
            true,
        ];
        yield 'version prefixes' => [
            [
                'guzzlehttp/guzzle'   => $seven ? 'v7.15.2' : 'v8.2.0',
                'guzzlehttp/promises' => $seven ? 'v2.5.1' : 'v3.0.2',
                'guzzlehttp/psr7'     => $seven ? 'v2.13.0' : 'v3.1.0',
            ],
            true,
        ];
        yield 'Guzzle below safe floor' => [['guzzlehttp/guzzle' => $seven ? '7.15.1' : '8.1.99'], false];
        yield 'Promises below supported floor' => [['guzzlehttp/promises' => $seven ? '2.5.0' : '3.0.1'], false];
        yield 'PSR7 below supported floor' => [['guzzlehttp/psr7' => $seven ? '2.12.99' : '3.0.99'], false];
        yield 'Guzzle different loaded major' => [['guzzlehttp/guzzle' => $seven ? '8.2.0' : '7.15.5'], false];
        yield 'unknown Guzzle major' => [['guzzlehttp/guzzle' => '9.0.0'], false];
        yield 'cross-major Promises' => [['guzzlehttp/promises' => $seven ? '3.0.2' : '2.5.3'], false];
        yield 'cross-major PSR7' => [['guzzlehttp/psr7' => $seven ? '3.1.0' : '2.13.1'], false];
        yield 'missing Guzzle package' => [['guzzlehttp/guzzle' => null], false];
        yield 'missing Promises package' => [['guzzlehttp/promises' => null], false];
        yield 'missing PSR7 package' => [['guzzlehttp/psr7' => null], false];
        yield 'unknown development alias' => [['guzzlehttp/guzzle' => 'dev-main'], false];
    }

    #[DataProvider('nativeApiShapes')]
    public function testNativeContractChangesFailClosed(
        string $constructor,
        string $invoke,
        bool $disableMulti,
        bool $supported,
        string $stack = '',
        string $interface = '',
    ): void {
        $code      = 'namespace GuzzleHttp {' . $stack . '} namespace GuzzleHttp\Handler {' . ($interface . 'class CurlFactory {public function __construct(int $maxHandles) {}}') . 'class CurlMultiHandler {' . $constructor . $invoke . 'public function tick(): void {}public function close(): void {}public function __destruct() {}}}namespace {require $argv[1] . "/Tests/bootstrap.php";try {\Netresearch\HttpGuard\Transport\RuntimeSupport::assertSupported();echo "SUPPORTED"; exit(0);}catch (\Netresearch\HttpGuard\PolicyException $error) {fwrite(STDERR, $error->reasonCode()); exit(3);}}';
        $arguments = [PHP_BINARY];
        if ($disableMulti) {
            $arguments[] = '-d';
            $arguments[] = 'disable_functions=curl_multi_exec';
        }
        array_push($arguments, '-r', $code, dirname(__DIR__, 4));
        // Controlled argv uses PHP_BINARY, fixed provider code/options and the repository path.
        // nosemgrep: php.lang.security.exec-use.exec-use
        $process = proc_open($arguments, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame($supported ? 0 : 3, proc_close($process), (string) $stderr);
        self::assertSame($supported ? 'SUPPORTED' : '', $stdout);
        self::assertSame($supported ? '' : 'transport_unsupported', $stderr);
    }

    /** @return iterable<string, array{0: string, 1: string, 2: bool, 3: bool, 4?: string, 5?: string}> */
    public static function nativeApiShapes(): iterable
    {
        $constructor = 'public function __construct(array $options = []) {}';
        $invoke      = 'public function __invoke($request, array $options) {}';
        yield 'constructor ignores native factory argument' => ['public function __construct() {}', $invoke, false, false];
        yield 'invocation ignores request options' => [$constructor, 'public function __invoke($request) {}', false, false];
        yield 'constructor needs an unknown mandatory argument' => ['public function __construct(array $options, int $unknown) {}', $invoke, false, false];
        yield 'compatible optional constructor argument' => ['public function __construct(array $options = [], int $optional = 0) {}', $invoke, false, true];
        yield 'missing native multi capability' => [$constructor, $invoke, true, false];
        yield 'middleware entries lose positional inventory' => [
            $constructor,
            $invoke,
            false,
            false,
            'class HandlerStack {private array $stack = []; public function push(callable $middleware, string $name = ""): void {$this->stack[] = ["handler" => $middleware, "name" => $name];}}',
        ];
        yield 'middleware inventory property removed' => [
            $constructor,
            $invoke,
            false,
            false,
            'class HandlerStack {private array $middlewares = []; public function push(callable $middleware, string $name = ""): void {$this->middlewares[] = [$middleware, $name];}}',
        ];
        yield 'factory interface widens request input' => [
            $constructor,
            $invoke,
            false,
            false,
            '',
            'interface CurlFactoryInterface {public function create(mixed $request, array $options): EasyHandle; public function release(EasyHandle $easy): void;}',
        ];
        yield 'factory interface adds an abstract method' => [
            $constructor,
            $invoke,
            false,
            false,
            '',
            'interface CurlFactoryInterface {public function create(\Psr\Http\Message\RequestInterface $request, array $options): EasyHandle; public function release(EasyHandle $easy): void;public function reset(): void;}',
        ];
        yield 'factory interface changes native return type' => [
            $constructor,
            $invoke,
            false,
            false,
            '',
            'interface CurlFactoryInterface {public function create(\Psr\Http\Message\RequestInterface $request, array $options): \stdClass; public function release(EasyHandle $easy): void;}',
        ];
        yield 'factory interface adds an optional argument' => [
            $constructor,
            $invoke,
            false,
            false,
            '',
            'interface CurlFactoryInterface {public function create(\Psr\Http\Message\RequestInterface $request, array $options, int $optional = 0): EasyHandle; public function release(EasyHandle $easy): void;}',
        ];
    }
}
