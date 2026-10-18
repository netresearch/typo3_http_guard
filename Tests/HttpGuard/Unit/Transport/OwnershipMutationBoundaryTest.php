<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\{Request, Response};
use Netresearch\HttpGuard\{AddressClassifier, GuardConfig, NativeOperation, NullDecisionReporter, PolicyEngine, PolicyException, PolicyRegistry, ResolverInterface, SystemClock, TargetNormalizer};
use Netresearch\HttpGuard\Transport\{BoundaryMiddleware, InvocationRegistry, OptionSanitizer, RequestEnvelope, RuntimeSupport};
use PHPUnit\Framework\Attributes\{DataProvider, PreserveGlobalState, RunInSeparateProcess, WithoutErrorHandler};
use PHPUnit\Framework\TestCase;

final class OwnershipMutationBoundaryTest extends TestCase
{
    #[DataProvider('invalidInvocationInputs')]
    public function testInvocationChecksBadEnvelopeAndCallerContextIndependently(bool $badEnvelope): void
    {
        $registry           = new PolicyRegistry(GuardConfig::fromArray([]), new SystemClock());
        $context            = $registry->newContext();
        $foreign            = $registry->newContext();
        $invocations        = new InvocationRegistry();
        [$envelope, $token] = $invocations->open($context, 'https://public.example:443', 'https', null);
        try {
            $invocations->assert($badEnvelope ? null : $envelope, $token, $badEnvelope ? $context : $foreign, $context);
            self::fail('Invalid invocation was accepted');
        } catch (PolicyException $error) {
            self::assertSame('grant_invalid', $error->reasonCode());
        }
        self::assertSame($envelope, $invocations->assert($envelope, $token, $context, $context));
        $invocations->close($envelope);
        self::assertSame(0, $invocations->activeCount());
    }

    public static function invalidInvocationInputs(): iterable
    {
        yield 'bad envelope with valid token' => [true];
        yield 'foreign context with valid token' => [false];
    }

    public function testDirectEnvelopeDefaultsToAnOrdinaryRequest(): void
    {
        self::assertFalse((new RequestEnvelope('https://public.example:443', 'https', null))->publicFetch);
    }

    public function testGuzzleManagedAuthenticationControlsAreRejectedOnOtherMajors(): void
    {
        $options = [
            'auth' => ['fixture-user', 'fixture-passphrase', 'digest'],
            'curl' => [CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_USERPWD => 'fixture-user:fixture-passphrase'],
        ];
        if (RuntimeSupport::major() !== 7) {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('option_forbidden');
        }
        $leaf = (new OptionSanitizer())->sanitize(new Request('GET', 'https://public.example'), $options);
        if (RuntimeSupport::major() === 7) {
            self::assertSame($options['curl'], $leaf['curl']);
        }
    }

    public function testOmittedRedirectOptionsDoNotEnableRedirectsUnderATighterLimit(): void
    {
        $config      = GuardConfig::fromArray(['redirects' => ['max' => 0]]);
        $clock       = new SystemClock();
        $registry    = new PolicyRegistry($config, $clock);
        $invocations = new InvocationRegistry();
        $resolver    = $this->createMock(ResolverInterface::class);
        $resolver->expects(self::never())->method('resolve');
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            new NullDecisionReporter(),
        );
        $boundary = new BoundaryMiddleware($engine, $config, $registry, $registry->newContext(), $invocations);
        $response = new Response(302, ['Location' => '/next']);
        $handle   = $boundary(static fn () => Create::promiseFor($response));
        self::assertSame($response, $handle(new Request('GET', 'https://public.example'), [])->wait());
        self::assertSame(0, $invocations->activeCount());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[WithoutErrorHandler]
    public function testNativeOperationFallsThroughWhenThereIsNoPreviousErrorHandler(): void
    {
        $probe    = static fn (): bool => false;
        $previous = set_error_handler($probe);
        restore_error_handler();
        self::assertNull($previous);
        $handled = NativeOperation::attempt(
            static function (): bool {
                $current = set_error_handler(static fn (): bool => false);
                restore_error_handler();
                self::assertIsCallable($current);

                return $current(E_USER_NOTICE, 'synthetic notice', __FILE__, __LINE__);
            },
        );
        self::assertFalse($handled);
        $current = set_error_handler($probe);
        restore_error_handler();
        self::assertNull($current);
    }
}
