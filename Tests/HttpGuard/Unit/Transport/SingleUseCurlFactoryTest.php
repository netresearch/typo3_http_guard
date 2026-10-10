<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use Closure;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Handler\CurlFactoryInterface;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\EasyHandle;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Transport\SingleUseCurlFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/** Counts calls before the delegate can allocate a native handle. */
final class CountingCurlFactory implements CurlFactoryInterface
{
    public int $creates           = 0;
    public int $releases          = 0;
    public ?Closure $duringCreate = null;

    public function __construct(private ?CurlFactoryInterface $native = null) {}

    public function create(RequestInterface $request, array $options): EasyHandle
    {
        ++$this->creates;
        ($this->duringCreate ?? static function (): void {})();
        if ($this->native !== null) {
            return $this->native->create($request, $options);
        }
        $easy          = new EasyHandle();
        $easy->request = $request;

        return $easy;
    }

    public function release(EasyHandle $easy): void
    {
        ++$this->releases;
        $this->native?->release($easy);
    }
}

final class SingleUseCurlFactoryTest extends TestCase
{
    public function testNormalCreateAndReleasePreserveThePublicFactoryContract(): void
    {
        $delegate = new CountingCurlFactory();
        $factory  = new SingleUseCurlFactory($delegate);
        $request  = new Request('GET', 'http://127.0.0.1:1/single-attempt-witness');
        $easy     = $factory->create($request, ['proxy' => '']);
        self::assertSame($request, $easy->request);
        $factory->release($easy);
        self::assertSame(1, $delegate->creates);
        self::assertSame(1, $delegate->releases);
        try {
            $factory->create($request, []);
            self::fail('A released lease admitted a second native attempt');
        } catch (PolicyException $error) {
            self::assertSame('transport_unsupported', $error->reasonCode());
        }
        self::assertSame(1, $delegate->creates);
    }

    public function testFailedPreparationCannotCreateAnotherHandle(): void
    {
        $delegate               = new CountingCurlFactory();
        $delegate->duringCreate = static function (): void {
            throw new RuntimeException('Synthetic preparation failure');
        };
        $factory = new SingleUseCurlFactory($delegate);
        $request = new Request('GET', 'http://127.0.0.1:1/single-attempt-witness');
        try {
            $factory->create($request, []);
            self::fail('Synthetic preparation unexpectedly succeeded');
        } catch (RuntimeException $error) {
            self::assertSame('Synthetic preparation failure', $error->getMessage());
        }
        try {
            $factory->create($request, []);
            self::fail('Failed preparation reopened the lease');
        } catch (PolicyException $error) {
            self::assertSame('transport_unsupported', $error->reasonCode());
        }
        self::assertSame(1, $delegate->creates);
    }

    public function testBodyPreparationReentryIsRejectedBeforeTheDelegate(): void
    {
        $delegate               = new CountingCurlFactory();
        $factory                = new SingleUseCurlFactory($delegate);
        $request                = new Request('GET', 'http://127.0.0.1:1/single-attempt-witness');
        $delegate->duringCreate = static function () use ($factory, $request): void {
            $factory->create($request, []);
        };
        try {
            $factory->create($request, []);
            self::fail('Reentrant preparation admitted another handle');
        } catch (PolicyException $error) {
            self::assertSame('transport_unsupported', $error->reasonCode());
        }
        self::assertSame(1, $delegate->creates);
    }

    public function testRealNativeHiddenRetryControlAdmitsAnotherHandleWithoutTheFence(): void
    {
        $this->nativeHiddenRetry(false);
    }

    public function testRealNativeHiddenRetryIsRejectedBeforeAnotherHandleWithoutPrivateCounters(): void
    {
        $this->nativeHiddenRetry(true);
    }

    private function nativeHiddenRetry(bool $guarded): void
    {
        $delegate = new CountingCurlFactory(new CurlFactory(0));
        $factory  = $guarded ? new SingleUseCurlFactory($delegate) : $delegate;
        $handler  = new CurlMultiHandler(['handle_factory' => $factory, 'transport_sharing' => 'none']);
        $request  = new Request('POST', 'http://127.0.0.1:1/single-attempt-witness');
        // No private retry counter and no handler tick: native handles never perform I/O.
        $easy = $factory->create($request, ['proxy' => '']);
        self::assertSame(1, $delegate->creates);
        $retry = null;
        try {
            // A real handle with no response and errno zero exercises Guzzle's public error finish path.
            $retry = CurlFactory::finish($handler, $easy, $factory);
            self::assertFalse($guarded, 'The hidden native retry passed the single-attempt fence');
            self::assertSame(2, $delegate->creates);
        } catch (PolicyException $error) {
            self::assertTrue($guarded);
            self::assertSame('transport_unsupported', $error->reasonCode());
            self::assertSame(1, $delegate->creates);
        } finally {
            $retry?->cancel();
            if (is_callable([$handler, 'close'])) {
                $handler->close();
            } else {
                $handler->__destruct();
            }
        }
        self::assertFalse(isset($easy->handle));
        self::assertGreaterThanOrEqual(1, $delegate->releases);
    }
}
