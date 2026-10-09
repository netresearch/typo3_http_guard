<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard\Transport;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\RequestPolicyContext;
use Netresearch\HttpGuard\TargetNormalizer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class BoundaryMiddleware
{
    private ?\Closure $registryAssertion = null;
    public function __construct(
        private readonly PolicyEngine $engine,
        private readonly GuardConfig $config,
        private readonly PolicyRegistry $policyRegistry,
        private readonly RequestPolicyContext $context,
        private readonly InvocationRegistry $invocations,
        private readonly bool $publicFetch = false
    )
    {
    }
    public function setRegistryAssertion(?\Closure $assertion): void
    {
        $this->registryAssertion = $assertion;
    }
    public function __invoke(callable $next): callable
    {
        return function (
            RequestInterface $request,
            array $options
        ) use ($next): PromiseInterface {
            try {
                ($this->registryAssertion ?? static function (): void {
                })();
                if ($this->config->mode !== 'enforce') {
                    return $next($request, $options);
                }
                foreach ([
                    'nr_http_guard_context',
                    'nr_http_guard_envelope',
                    'nr_http_guard_invocation',
                    'nr_http_guard_grant',
                ] as $key) {
                    if (array_key_exists($key, $options)) {
                        throw new PolicyException('grant_invalid');
                    }
                }
                $target = (new TargetNormalizer())->normalize($request);
                $this->policyRegistry->validateContext($this->context);
                $sanitizer = new OptionSanitizer(
                    $this->config->data['redirects']['max'],
                    $this->config->data['tls']['requireVerification']
                );
                $sanitizer->assertRedirects(
                    $options['allow_redirects'] ?? false
                );
                $handler = $options['handler'] ?? null;
                if ($handler !== null && !$handler instanceof HandlerStack) {
                    throw new PolicyException('option_forbidden');
                }
                [$envelope, $token] = $this->invocations->open(
                    $this->context,
                    $target->origin,
                    $target->scheme,
                    $handler,
                    $this->publicFetch
                );
                $options['nr_http_guard_context'] = $this->context;
                $options['nr_http_guard_envelope'] = $envelope;
                $options['nr_http_guard_invocation'] = $token;
                try {
                    $inner = $next($target->canonicalRequest, $options);
                } catch (\Throwable $error) {
                    $this->invocations->close($envelope);
                    throw $error;
                }
                $finish = function (
                    ResponseInterface $response
                ) use ($target, $options, $envelope): ResponseInterface {
                    return $this->validateResponse(
                        $response,
                        $target->canonicalRequest,
                        $options,
                        $envelope
                    );
                };
                $outer = null;
                $outer = new Promise(
                    function () use ($inner, $finish, $envelope, &$outer): void {
                        try {
                            $response = $inner->wait();
                            if (self::published($outer)->getState() === PromiseInterface::PENDING) {
                                self::published($outer)->resolve(
                                    $finish($response)
                                );
                            }
                        } catch (\Throwable $error) {
                            if (self::published($outer)->getState() === PromiseInterface::PENDING) {
                                self::published($outer)->reject($error);
                            }
                        } finally {
                            $this->invocations->close($envelope);
                        }
                    },
                    function () use ($inner, $envelope): void {
                        $this->invocations->close($envelope);
                        $inner->cancel();
                    }
                );
                $inner->then(
                    function (
                        ResponseInterface $response
                    ) use ($finish, $envelope, &$outer): void {
                        try {
                            if (self::published($outer)->getState() === PromiseInterface::PENDING) {
                                self::published($outer)->resolve(
                                    $finish($response)
                                );
                            }
                        } catch (\Throwable $error) {
                            if (self::published($outer)->getState() === PromiseInterface::PENDING) {
                                self::published($outer)->reject($error);
                            }
                        } finally {
                            $this->invocations->close($envelope);
                        }
                    },
                    function ($reason) use ($envelope, &$outer): void {
                        $this->invocations->close($envelope);
                        if (self::published($outer)->getState() === PromiseInterface::PENDING) {
                            self::published($outer)->reject($reason);
                        }
                    }
                );
                return $outer;
            } catch (PolicyException $error) {
                $this->engine->reportDiagnostic(
                    $this->context,
                    'deny',
                    $error->reasonCode()
                );
                return Create::rejectionFor($error);
            }
        };
    }
    /** @param array<string,mixed> $options */
    private function validateResponse(
        ResponseInterface $response,
        RequestInterface $request,
        array $options,
        RequestEnvelope $envelope
    ): ResponseInterface
    {
        try {
            if (empty($options['allow_redirects']) || !$response->hasHeader('Location') || !in_array(
                $response->getStatusCode(),
                [301, 302, 303, 307, 308],
                true
            )) {
                return $response;
            }
            $profile = $this->policyRegistry->validateContext($this->context);
            if ($profile !== null && $profile->redirects === 'none') {
                throw new PolicyException('redirect_forbidden');
            }
            $locations = $response->getHeader('Location');
            if (count($locations) !== 1 || strpbrk($locations[0], "\r\n\\") !== false || str_contains($locations[0], '#')) {
                throw new PolicyException('redirect_forbidden');
            }
            try {
                $uri = UriResolver::resolve($request->getUri(), new Uri($locations[0]));
                $target = (new TargetNormalizer())->normalize($request->withUri($uri));
            } catch (\Throwable) {
                throw new PolicyException('redirect_forbidden');
            }
            if ($envelope->scheme === 'https' && $target->scheme !== 'https' || !$envelope->publicFetch && $target->origin !== $envelope->origin) {
                throw new PolicyException('redirect_forbidden');
            }
            return $response;
        } catch (PolicyException $error) {
            $this->engine->reportDiagnostic(
                $this->context,
                'deny',
                $error->reasonCode()
            );
            throw $error;
        }
    }
    private static function published(?Promise $promise): Promise
    {
        if ($promise === null) {
            throw new \LogicException("Promise not published");
        }
        return $promise;
    }
}
