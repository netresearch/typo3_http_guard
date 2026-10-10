<?php

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Transport;

use Closure;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\RequestPolicyContext;
use Netresearch\HttpGuard\TargetNormalizer;
use Psr\Http\Message\RequestInterface;

final class TerminalGuardMiddleware
{
    /** @var Closure(): void|null */
    private ?Closure $registryAssertion = null;

    public function __construct(
        private readonly PolicyEngine $engine,
        private readonly GuardConfig $config,
        private readonly RequestPolicyContext $context,
        private readonly InvocationRegistry $invocations,
        private readonly GuardedTransferFactory $transfers,
    ) {}

    /** @param Closure(): void|null $assertion */
    public function setRegistryAssertion(?Closure $assertion): void
    {
        $this->registryAssertion = $assertion;
    }

    /**
     * @param callable(RequestInterface, array<array-key, mixed>): PromiseInterface $next
     *
     * @return callable(RequestInterface, array<array-key, mixed>): PromiseInterface
     */
    public function __invoke(callable $next): callable
    {
        return function (RequestInterface $request, array $options) use ($next): PromiseInterface {
            try {
                ($this->registryAssertion ?? static function (): void {})();
                if ($this->config->mode === 'disabled') {
                    return $next($request, $options);
                }
                $sanitizer = new OptionSanitizer(
                    $this->config->data['redirects']['max'],
                    $this->config->data['tls']['requireVerification'],
                );
                if ($this->config->mode === 'observe') {
                    $this->engine->evaluate($request, $this->context);
                    try {
                        RuntimeSupport::assertSupported();
                        $sanitizer->sanitize(
                            $request,
                            $options,
                            ($options['handler'] ?? null) instanceof \GuzzleHttp\HandlerStack ? $options['handler'] : null,
                        );
                    } catch (PolicyException $error) {
                        $this->engine->reportDiagnostic($this->context, 'unverifiable', $error->reasonCode());
                    }

                    return $next($request, $options);
                }
                $envelope = $this->invocations->assert(
                    $options['nr_http_guard_envelope'] ?? null,
                    $options['nr_http_guard_invocation'] ?? null,
                    $options['nr_http_guard_context'] ?? null,
                    $this->context,
                );
                if (array_key_exists('nr_http_guard_grant', $options)) {
                    throw new PolicyException('grant_invalid');
                }
                $target = (new TargetNormalizer())->normalize($request);
                if ($target->origin !== $envelope->origin) {
                    throw new PolicyException('authority_mismatch');
                }
                if ($envelope->publicFetch && (!in_array($request->getMethod(), ['GET', 'HEAD'], true) || $request->getBody()->getSize() !== 0 || array_diff(
                    array_map(strtolower(...), array_keys($request->getHeaders())),
                    ['host', 'accept', 'accept-encoding', 'user-agent'],
                ) !== [])) {
                    throw new PolicyException('option_forbidden');
                }
                unset($options['nr_http_guard_context'], $options['nr_http_guard_envelope'], $options['nr_http_guard_invocation']);
                $leaf = $sanitizer->sanitize($target->canonicalRequest, $options, $envelope->expectedHandler);
                RuntimeSupport::assertSupported();

                return $this->transfers
                    ->send($target->canonicalRequest, $leaf)
                    ->then(
                        null,
                        function ($reason) use ($envelope): PromiseInterface {
                            if ($reason instanceof PolicyException) {
                                $this->invocations->close($envelope);
                            }

                            return Create::rejectionFor($reason);
                        },
                    );
            } catch (PolicyException $error) {
                if (($envelope ?? null) instanceof RequestEnvelope) {
                    $this->invocations->close($envelope);
                }
                $this->engine->reportDiagnostic($this->context, 'deny', $error->reasonCode());

                return Create::rejectionFor($error);
            }
        };
    }
}
