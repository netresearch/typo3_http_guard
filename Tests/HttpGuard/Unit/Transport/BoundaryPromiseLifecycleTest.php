<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\InvocationRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

final class BoundaryPromiseLifecycleTest extends TestCase
{
    #[DataProvider('ordinaryResponses')]
    public function testCompletedResponsesPreserveResponseIdentityAndCloseTheInvocation(
        int $status,
        array $headers,
        mixed $redirects,
        bool $publicFetch,
    ): void {
        [$boundary, $invocations] = $this->boundary('enforce', $publicFetch);
        $response                 = new Response($status, $headers, 'ordinary fixture');
        $calls                    = 0;
        $handle                   = $boundary(
            static function (RequestInterface $request, array $options) use ($response, &$calls): PromiseInterface {
                ++$calls;
                self::assertSame('https://api.example/path', (string) $request->getUri());
                self::assertArrayHasKey('nr_http_guard_context', $options);
                self::assertArrayHasKey('nr_http_guard_envelope', $options);
                self::assertArrayHasKey('nr_http_guard_invocation', $options);

                return Create::promiseFor($response);
            },
        );
        $promise = $handle(new Request('GET', 'https://api.example/path'), ['allow_redirects' => $redirects]);
        self::assertSame($response, $promise->wait());
        Utils::queue()->run();
        self::assertSame(PromiseInterface::FULFILLED, $promise->getState());
        self::assertSame(1, $calls);
        self::assertSame(0, $invocations->activeCount());
    }

    public function testPendingResponsePublishesThenClosesThroughTheAsyncQueue(): void
    {
        [$boundary, $invocations] = $this->boundary();
        $inner                    = new Promise();
        $handle                   = $boundary(static fn (): PromiseInterface => $inner);
        $outer                    = $handle(new Request('GET', 'https://api.example'), []);
        self::assertSame(PromiseInterface::PENDING, $outer->getState());
        self::assertSame(1, $invocations->activeCount());
        $response = new Response(200);
        $inner->resolve($response);
        Utils::queue()->run();
        self::assertSame(PromiseInterface::FULFILLED, $outer->getState());
        self::assertSame($response, $outer->wait());
        self::assertSame(0, $invocations->activeCount());
    }

    public function testInnerFailureKeepsTheOriginalExceptionAndClosesTheInvocation(): void
    {
        [$boundary, $invocations] = $this->boundary();
        $error                    = new RuntimeException('ordinary local handler failure');
        $inner                    = new Promise();
        $handle                   = $boundary(static fn (): PromiseInterface => $inner);
        $outer                    = $handle(new Request('GET', 'https://api.example'), []);
        $inner->reject($error);
        Utils::queue()->run();
        self::assertSame(PromiseInterface::REJECTED, $outer->getState());
        self::assertSame(0, $invocations->activeCount());
        try {
            $outer->wait();
            self::fail('Local handler exception was lost');
        } catch (RuntimeException $actual) {
            self::assertSame($error, $actual);
        }
    }

    public function testSynchronousHandlerFailureClosesBeforePropagating(): void
    {
        [$boundary, $invocations] = $this->boundary();
        $error                    = new RuntimeException('ordinary synchronous handler failure');
        $handle                   = $boundary(
            static function () use ($error): never {
                throw $error;
            },
        );
        try {
            $handle(new Request('GET', 'https://api.example'), []);
            self::fail('Local handler exception was lost');
        } catch (RuntimeException $actual) {
            self::assertSame($error, $actual);
        }
        self::assertSame(0, $invocations->activeCount());
    }

    public function testCancellationReachesTheInnerPromiseAndClosesExactlyOnce(): void
    {
        [$boundary, $invocations] = $this->boundary();
        $cancellations            = 0;
        $inner                    = new Promise(
            null,
            static function () use (&$cancellations): void {
                ++$cancellations;
            },
        );
        $handle = $boundary(static fn (): PromiseInterface => $inner);
        $outer  = $handle(new Request('GET', 'https://api.example'), []);
        self::assertSame(1, $invocations->activeCount());
        $outer->cancel();
        $outer->cancel();
        Utils::queue()->run();
        self::assertSame(1, $cancellations);
        self::assertSame(PromiseInterface::REJECTED, $outer->getState());
        self::assertSame(0, $invocations->activeCount());
    }

    #[DataProvider('nonEnforcingModes')]
    public function testNonEnforcingModesPreserveOrdinaryHandlerInputs(string $mode): void
    {
        [$boundary, $invocations] = $this->boundary($mode);
        $request                  = new Request('GET', 'https://api.example');
        $options                  = ['timeout' => 1.25];
        $response                 = new Response(200);
        $inner                    = Create::promiseFor($response);
        $assertions               = 0;
        $boundary->setRegistryAssertion(
            static function () use (&$assertions): void {
                ++$assertions;
            },
        );
        $handle = $boundary(
            static function (
                RequestInterface $actualRequest,
                array $actualOptions,
            ) use ($request, $options, $inner): PromiseInterface {
                self::assertSame($request, $actualRequest);
                self::assertSame($options, $actualOptions);

                return $inner;
            },
        );
        self::assertSame($inner, $handle($request, $options));
        self::assertSame(1, $assertions);
        self::assertSame($response, $inner->wait());
        self::assertSame(0, $invocations->activeCount());
    }

    /** @return iterable<string, array{int, array<string,string>, mixed, bool}> */
    public static function ordinaryResponses(): iterable
    {
        yield 'success' => [200, [], false, false];
        yield 'not found' => [404, [], false, false];
        yield 'redirect disabled' => [302, ['Location' => '/next'], false, false];
        yield 'empty redirect settings' => [302, ['Location' => '/next'], [], false];
        yield 'same origin relative redirect' => [301, ['Location' => '/next'], true, false];
        yield 'same origin absolute redirect' => [307, ['Location' => 'https://api.example/next'], true, false];
        yield 'public fetch next origin' => [308, ['Location' => 'https://other.example/next'], true, true];
        yield 'ordinary location on success' => [200, ['Location' => '/next'], true, false];
    }

    /** @return iterable<string, array{string}> */
    public static function nonEnforcingModes(): iterable
    {
        yield 'disabled' => ['disabled'];
        yield 'observe' => ['observe'];
    }

    /** @return array{BoundaryMiddleware,InvocationRegistry} */
    private function boundary(string $mode = 'enforce', bool $publicFetch = false): array
    {
        $config   = GuardConfig::fromArray(['mode' => $mode]);
        $clock    = new SystemClock();
        $registry = new PolicyRegistry($config, $clock);
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->expects(self::never())->method('resolve');
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            new NullDecisionReporter(),
        );
        $invocations = new InvocationRegistry();

        return [
            new BoundaryMiddleware($engine, $config, $registry, $registry->newContext(), $invocations, $publicFetch),
            $invocations,
        ];
    }
}
