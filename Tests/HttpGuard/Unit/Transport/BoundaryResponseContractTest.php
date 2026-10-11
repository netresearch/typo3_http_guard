<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures\LifecyclePolicy;
use Netresearch\HttpGuard\Transport\{BoundaryMiddleware, InvocationRegistry};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use RuntimeException;
use stdClass;

final class BoundaryResponseContractTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/Fixtures/TransferLifecycleHarness.php';
    }

    private function boundary(?string $profile = null, bool $publicFetch = false, bool $expiring = false): array
    {
        $policy      = new LifecyclePolicy($profile, $expiring);
        $invocations = new InvocationRegistry();
        $context     = $policy->registry->newContext($profile !== null || $expiring ? 'fixture' : null);

        return [
            new BoundaryMiddleware(
                $policy->engine,
                $policy->config,
                $policy->registry,
                $context,
                $invocations,
                $publicFetch,
            ),
            $invocations,
            $policy,
            $context,
        ];
    }

    private function rejection(PromiseInterface $outer): mixed
    {
        $reason = null;
        $outer->then(
            null,
            static function (mixed $value) use (&$reason): void {
                $reason = $value;
            },
        );
        Utils::queue()->run();
        self::assertSame(PromiseInterface::REJECTED, $outer->getState());

        return $reason;
    }

    #[DataProvider('reservedInputs')]
    public function testReservedInvocationOptionsRejectEvenNullWithoutOpeningAnInvocation(string $key): void
    {
        [$boundary, $invocations, $policy] = $this->boundary();
        $calls                             = 0;
        $handle                            = $boundary(
            static function () use (&$calls): never {
                ++$calls;
                throw new RuntimeException('must not reach handler');
            },
        );
        $outer = $handle(new Request('GET', 'https://public.example'), [$key => null]);
        $error = $this->rejection($outer);
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame('grant_invalid', $error->reasonCode());
        self::assertSame(0, $calls);
        self::assertSame(0, $invocations->activeCount());
        self::assertSame(['grant_invalid'], array_column($policy->reporter->events, 'reasonCode'));
    }

    public static function reservedInputs(): iterable
    {
        foreach (['nr_http_guard_context', 'nr_http_guard_envelope', 'nr_http_guard_invocation', 'nr_http_guard_grant'] as $key) {
            yield $key => [$key];
        }
    }

    #[DataProvider('untrustedHandlers')]
    public function testUntrustedHandlerOptionsRejectBeforeNextHandler(mixed $handler): void
    {
        [$boundary, $invocations, $policy] = $this->boundary();
        $handle                            = $boundary(
            static function (): never {
                self::fail('Untrusted handler reached send');
            },
        );
        $outer = $handle(new Request('GET', 'https://public.example'), ['handler' => $handler]);
        $error = $this->rejection($outer);
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame('option_forbidden', $error->reasonCode());
        self::assertSame(0, $invocations->activeCount());
        self::assertSame(['option_forbidden'], array_column($policy->reporter->events, 'reasonCode'));
    }

    public static function untrustedHandlers(): iterable
    {
        yield 'false' => [false];
        yield 'string' => ['caller handler'];
        yield 'array' => [[]];
        yield 'object' => [new stdClass()];
        yield 'callable' => [static fn () => null];
    }

    public function testCanonicalInvocationCarriesExactRegistryContextAndExpectedStack(): void
    {
        [$boundary, $invocations, $policy, $context] = $this->boundary();
        $stack                                       = new HandlerStack();
        $captured                                    = [];
        $inner                                       = new Promise();
        $handle                                      = $boundary(
            static function (
                RequestInterface $request,
                array $options,
            ) use (&$captured, $inner, $context, $invocations, $stack): PromiseInterface {
                self::assertSame('https://public.example/path?q=value', (string) $request->getUri());
                self::assertSame($context, $options['nr_http_guard_context']);
                $envelope = $invocations->assert(
                    $options['nr_http_guard_envelope'],
                    $options['nr_http_guard_invocation'],
                    $context,
                    $context,
                );
                self::assertSame($stack, $envelope->expectedHandler);
                self::assertSame('https://public.example', $envelope->origin);
                self::assertSame('https', $envelope->scheme);
                self::assertFalse($envelope->publicFetch);
                self::assertSame(1.25, $options['timeout']);
                self::assertSame(['kept' => 'value'], $options['ordinary']);
                $captured = $options;

                return $inner;
            },
        );
        $outer = $handle(
            new Request('GET', 'https://PUBLIC.EXAMPLE.:443/path?q=value'),
            ['handler' => $stack, 'timeout' => 1.25, 'ordinary' => ['kept' => 'value']],
        );
        self::assertSame(1, $invocations->activeCount());
        $inner->resolve(new Response());
        Utils::queue()->run();
        self::assertSame(0, $invocations->activeCount());
        self::assertSame(PromiseInterface::FULFILLED, $outer->getState());
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('grant_invalid');
        $invocations->assert(
            $captured['nr_http_guard_envelope'],
            $captured['nr_http_guard_invocation'],
            $context,
            $context,
        );
    }

    #[DataProvider('redirectContracts')]
    public function testRedirectResponsesRespectOriginSchemeProfileAndHeaderShape(
        string $from,
        int $status,
        array $locations,
        bool $publicFetch,
        ?string $profile,
        bool $accepted,
    ): void {
        [$boundary, $invocations, $policy] = $this->boundary($profile, $publicFetch);
        $response                          = $this->createMock(ResponseInterface::class);
        $response->method('hasHeader')->with('Location')->willReturn(true);
        $response->method('getHeader')->with('Location')->willReturn($locations);
        $response->method('getStatusCode')->willReturn($status);
        $inner  = new Promise();
        $handle = $boundary(static fn (): PromiseInterface => $inner);
        $outer  = $handle(new Request('GET', $from), ['allow_redirects' => true]);
        self::assertSame(1, $invocations->activeCount());
        $inner->resolve($response);
        Utils::queue()->run();
        if ($accepted) {
            self::assertSame($response, $outer->wait());
            self::assertSame([], $policy->reporter->events);
        } else {
            $error = $this->rejection($outer);
            self::assertInstanceOf(PolicyException::class, $error);
            self::assertSame('redirect_forbidden', $error->reasonCode());
            self::assertSame(['redirect_forbidden'], array_column($policy->reporter->events, 'reasonCode'));
        }
        self::assertSame(0, $invocations->activeCount());
    }

    public static function redirectContracts(): iterable
    {
        foreach ([301, 302, 303, 307, 308] as $status) {
            yield 'same origin ' . $status => ['https://public.example/path', $status, ['/next'], false, null, true];
            yield 'foreign endpoint ' . $status => ['https://public.example/path', $status, ['https://other.example/next'], false, null, false];
        }
        foreach ([200, 201, 300, 304, 305, 306, 309, 404] as $status) {
            yield 'not a followed redirect ' . $status => ['https://public.example/path', $status, ['https://other.example/next'], false, null, true];
        }
        yield 'public next origin' => ['https://public.example/path', 302, ['https://other.example/next'], true, null, true];
        yield 'public https downgrade' => ['https://public.example/path', 302, ['http://other.example/next'], true, null, false];
        yield 'endpoint https downgrade' => ['https://public.example/path', 302, ['http://public.example/next'], false, null, false];
        yield 'public http upgrade' => ['http://public.example/path', 302, ['https://public.example/next'], true, null, true];
        yield 'public http next origin' => ['http://public.example/path', 302, ['http://other.example/next'], true, null, true];
        yield 'same origin canonical authority' => ['https://public.example/path', 302, ['https://PUBLIC.EXAMPLE.:443/next'], false, null, true];
        yield 'port is part of origin' => ['https://public.example/path', 302, ['https://public.example:8443/next'], false, null, false];
        yield 'no endpoint redirects' => ['https://public.example/path', 302, ['/next'], false, 'none', false];
        yield 'endpoint same origin allowed' => ['https://public.example/path', 302, ['/next'], false, 'same-origin', true];
        yield 'endpoint next origin forbidden' => ['https://public.example/path', 302, ['https://other.example/next'], false, 'same-origin', false];
        yield 'empty location list' => ['https://public.example/path', 302, [], false, null, false];
        yield 'two locations' => ['https://public.example/path', 302, ['/first', '/second'], false, null, false];
        foreach ([
            "/next\rX",
            "/next\nX",
            '/next\other',
            '/next#fragment',
            'ftp://public.example/next',
            'https://user:pass@public.example/next',
            'https://[127.0.0.1]/',
        ] as $location) {
            yield 'malformed ' . bin2hex($location) => ['https://public.example/path', 302, [$location], false, null, false];
        }
    }

    public function testExpiredEndpointRejectsRedirectAtResponseTimeAndClosesInvocation(): void
    {
        [$boundary, $invocations, $policy] = $this->boundary('same-origin', false, true);
        $inner                             = new Promise();
        $outer                             = $boundary(static fn (): PromiseInterface => $inner)(
            new Request('GET', 'https://public.example/path'),
            ['allow_redirects' => true]
        );
        $policy->clock->seconds = 1;
        $inner->resolve(new Response(302, ['Location' => '/next']));
        Utils::queue()->run();
        $error = $this->rejection($outer);
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame('grant_invalid', $error->reasonCode());
        self::assertSame(0, $invocations->activeCount());
        self::assertSame(['grant_invalid'], array_column($policy->reporter->events, 'reasonCode'));
    }

    #[DataProvider('waitOutcomes')]
    public function testOuterWaitUsesRealInnerWaitAndAlwaysClosesInvocation(string $outcome): void
    {
        [$boundary, $invocations, $policy] = $this->boundary();
        $waits                             = 0;
        $expected                          = new Response(206);
        $failure                           = new RuntimeException('wait callback failed');
        $inner                             = null;
        $inner                             = new Promise(
            static function () use (&$inner, &$waits, $outcome, $expected, $failure): void {
                ++$waits;
                if ($outcome === 'throws') {
                    throw $failure;
                }
                if ($outcome === 'rejects') {
                    $inner->reject($failure);
                } else {
                    $inner->resolve($outcome === 'malformed' ? 'not a response' : $expected);
                }
            },
        );
        $outer = $boundary(static fn (): PromiseInterface => $inner)(new Request('GET', 'https://public.example'), []);
        self::assertSame(1, $invocations->activeCount());
        if ($outcome === 'response') {
            self::assertSame($expected, $outer->wait());
        } else {
            try {
                $outer->wait();
                self::fail('Invalid result accepted');
            } catch (RuntimeException $actual) {
                if ($outcome === 'malformed') {
                    self::assertInstanceOf(PolicyException::class, $actual);
                    self::assertSame('transport_unsupported', $actual->reasonCode());
                } else {
                    self::assertSame($failure, $actual);
                }
            }
        }
        self::assertSame(1, $waits);
        self::assertSame(0, $invocations->activeCount());
    }

    public static function waitOutcomes(): iterable
    {
        foreach (['response', 'rejects', 'throws', 'malformed'] as $mode) {
            yield $mode => [$mode];
        }
    }

    #[DataProvider('settlementCombinations')]
    public function testLateInnerSettlementCannotOverwriteOuterStateButClosesInvocation(
        bool $outerReject,
        bool $innerReject,
    ): void {
        [$boundary, $invocations, $policy] = $this->boundary();
        $inner                             = new Promise();
        $outer                             = $boundary(static fn (): PromiseInterface => $inner)(
            new Request('GET', 'https://public.example'),
            ['allow_redirects' => true]
        );
        $chosen = $outerReject ? new RuntimeException('chosen outer failure') : new Response(202);
        if ($outerReject) {
            $outer->reject($chosen);
        } else {
            $outer->resolve($chosen);
        }
        if ($innerReject) {
            $inner->reject('later rejection');
        } else {
            $inner->resolve(new Response(302, ['Location' => 'http://foreign.example/next']));
        }
        Utils::queue()->run();
        if ($outerReject) {
            self::assertSame($chosen, $this->rejection($outer));
        } else {
            self::assertSame($chosen, $outer->wait());
        }
        self::assertSame(0, $invocations->activeCount());
        self::assertSame([], $policy->reporter->events, 'Already-settled response must not be validated again');
    }

    public static function settlementCombinations(): iterable
    {
        foreach ([false, true] as $outer) {
            foreach ([false, true] as $inner) {
                yield (int) $outer . '-' . (int) $inner => [$outer, $inner];
            }
        }
    }

    public function testConcurrentInvocationsKeepOtherPromiseLiveUntilItsOwnSettlement(): void
    {
        [$boundary, $invocations] = $this->boundary();
        $first                    = new Promise();
        $second                   = new Promise();
        $queue                    = [$first, $second];
        $handle                   = $boundary(
            static function () use (&$queue): PromiseInterface {
                return array_shift($queue);
            },
        );
        $one = $handle(new Request('GET', 'https://public.example/one'), []);
        $two = $handle(new Request('GET', 'https://public.example/two'), []);
        self::assertSame(2, $invocations->activeCount());
        $first->resolve(new Response(200));
        Utils::queue()->run();
        self::assertSame(1, $invocations->activeCount());
        self::assertSame(PromiseInterface::PENDING, $two->getState());
        $second->reject('exact scalar rejection');
        self::assertSame('exact scalar rejection', $this->rejection($two));
        self::assertSame(0, $invocations->activeCount());
        self::assertInstanceOf(Response::class, $one->wait());
    }

    public function testRegistryAssertionFailureIsReportedBeforeOpeningOrCallingNext(): void
    {
        [$boundary, $invocations, $policy] = $this->boundary();
        $failure                           = new PolicyException('grant_invalid');
        $boundary->setRegistryAssertion(
            static function () use ($failure): never {
                throw $failure;
            },
        );
        $handle = $boundary(
            static function (): never {
                self::fail('Registry rejection reached send');
            },
        );
        $outer = $handle(new Request('GET', 'https://public.example'), []);
        self::assertSame($failure, $this->rejection($outer));
        self::assertSame(0, $invocations->activeCount());
        self::assertSame(['grant_invalid'], array_column($policy->reporter->events, 'reasonCode'));
    }

    #[DataProvider('invalidRedirectOptions')]
    public function testMalformedRedirectOptionsRejectBeforeOpeningAnInvocation(mixed $redirects): void
    {
        [$boundary, $invocations, $policy] = $this->boundary();
        $handle                            = $boundary(
            static function (): never {
                self::fail('Invalid redirect options reached handler');
            },
        );
        $outer = $handle(new Request('GET', 'https://public.example'), ['allow_redirects' => $redirects]);
        $error = $this->rejection($outer);
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame('redirect_forbidden', $error->reasonCode());
        self::assertSame(0, $invocations->activeCount());
        self::assertSame(['redirect_forbidden'], array_column($policy->reporter->events, 'reasonCode'));
    }

    public static function invalidRedirectOptions(): iterable
    {
        yield 'string' => ['enabled'];
        yield 'integer' => [1];
        yield 'object' => [new stdClass()];
        yield 'excessive maximum' => [['max' => 6]];
        yield 'nonboolean strict' => [['strict' => 1]];
        yield 'unsafe protocols' => [['protocols' => ['ftp']]];
        yield 'unknown key' => [['extra' => true]];
    }

    #[DataProvider('nonFollowingOptions')]
    public function testUnfollowedRedirectResponseIsPreservedWithoutApplyingEndpointRedirectPolicy(
        array $options,
    ): void {
        [$boundary, $invocations, $policy] = $this->boundary('none');
        $inner                             = new Promise();
        $outer                             = $boundary(static fn (): PromiseInterface => $inner)(new Request('GET', 'https://public.example/path'), $options);
        $response                          = new Response(302, ['Location' => 'http://other.example/next']);
        $inner->resolve($response);
        Utils::queue()->run();
        self::assertSame($response, $outer->wait());
        self::assertSame(0, $invocations->activeCount());
        self::assertSame([], $policy->reporter->events);
    }

    public static function nonFollowingOptions(): iterable
    {
        yield 'omitted redirect option' => [[]];
        yield 'explicit false' => [['allow_redirects' => false]];
        yield 'empty settings' => [['allow_redirects' => []]];
    }

    public function testDefaultBoundaryIsAnEndpointBoundaryWithSameOriginRedirectRestriction(): void
    {
        $policy      = new LifecyclePolicy();
        $invocations = new InvocationRegistry();
        $boundary    = new BoundaryMiddleware(
            $policy->engine,
            $policy->config,
            $policy->registry,
            $policy->registry->newContext(),
            $invocations,
        );
        $inner = new Promise();
        $outer = $boundary(static fn (): PromiseInterface => $inner)(
            new Request('GET', 'https://public.example/path'),
            ['allow_redirects' => true]
        );
        $inner->resolve(new Response(302, ['Location' => 'https://other.example/next']));
        $error = $this->rejection($outer);
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame('redirect_forbidden', $error->reasonCode());
        self::assertSame(0, $invocations->activeCount());
    }

    public function testExpiredContextRejectsBeforeHandlerInvocationEvenWithoutRedirects(): void
    {
        [$boundary, $invocations, $policy] = $this->boundary('none', false, true);
        $policy->clock->seconds            = 1;
        $handle                            = $boundary(
            static function (): never {
                self::fail('Expired grant reached handler');
            },
        );
        $error = $this->rejection($handle(new Request('GET', 'https://public.example'), []));
        self::assertInstanceOf(PolicyException::class, $error);
        self::assertSame('grant_invalid', $error->reasonCode());
        self::assertSame(0, $invocations->activeCount());
    }

    public function testSynchronousFailureInvalidatesRetainedInvocationOptionsImmediately(): void
    {
        [$boundary, $invocations, $policy, $context] = $this->boundary();
        $retained                                    = [];
        $error                                       = new RuntimeException('synchronous failure');
        $handle                                      = $boundary(
            static function (RequestInterface $request, array $options) use (&$retained, $error): never {
                $retained = $options;
                throw $error;
            },
        );
        try {
            $handle(new Request('GET', 'https://public.example'), []);
            self::fail('Handler failure lost');
        } catch (RuntimeException $actual) {
            self::assertSame($error, $actual);
        }
        self::assertSame(0, $invocations->activeCount());
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('grant_invalid');
        $invocations->assert(
            $retained['nr_http_guard_envelope'],
            $retained['nr_http_guard_invocation'],
            $context,
            $context,
        );
    }

    public function testCancellationInvalidatesRetainedInvocationBeforePromiseQueueRuns(): void
    {
        [$boundary, $invocations, $policy, $context] = $this->boundary();
        $retained                                    = [];
        $cancels                                     = 0;
        $inner                                       = new Promise(
            null,
            static function () use (&$cancels): void {
                ++$cancels;
            },
        );
        $handle = $boundary(
            static function (RequestInterface $request, array $options) use (&$retained, $inner): PromiseInterface {
                $retained = $options;

                return $inner;
            },
        );
        $outer = $handle(new Request('GET', 'https://public.example'), []);
        $outer->cancel();
        self::assertSame(1, $cancels);
        self::assertSame(0, $invocations->activeCount());
        try {
            $invocations->assert(
                $retained['nr_http_guard_envelope'],
                $retained['nr_http_guard_invocation'],
                $context,
                $context,
            );
            self::fail('Cancelled invocation remained live');
        } catch (PolicyException $error) {
            self::assertSame('grant_invalid', $error->reasonCode());
        }
        Utils::queue()->run();
        self::assertSame(0, $invocations->activeCount());
    }

    public function testInnerRejectionClosesEvenWhenCallerRetainsEnvelopeAndToken(): void
    {
        [$boundary, $invocations, $policy, $context] = $this->boundary();
        $retained                                    = [];
        $inner                                       = new Promise();
        $handle                                      = $boundary(
            static function (RequestInterface $request, array $options) use (&$retained, $inner): PromiseInterface {
                $retained = $options;

                return $inner;
            },
        );
        $outer = $handle(new Request('GET', 'https://public.example'), []);
        $inner->reject('exact rejection');
        self::assertSame('exact rejection', $this->rejection($outer));
        self::assertSame(0, $invocations->activeCount());
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('grant_invalid');
        $invocations->assert(
            $retained['nr_http_guard_envelope'],
            $retained['nr_http_guard_invocation'],
            $context,
            $context,
        );
    }
}
