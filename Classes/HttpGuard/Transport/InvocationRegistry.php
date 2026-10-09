<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard\Transport;

use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\RequestPolicyContext;
use GuzzleHttp\HandlerStack;
final class InvocationRegistry
{
    /** @var \WeakMap<RequestEnvelope,array{token:InvocationToken,context:RequestPolicyContext}> */
    private \WeakMap $live;
    public function __construct()
    {
        $this->live = new \WeakMap();
    }
    /** @return array{RequestEnvelope,InvocationToken} */
    public function open(
        RequestPolicyContext $context,
        string $origin,
        string $scheme,
        ?HandlerStack $handler,
        bool $publicFetch = false
    ): array
    {
        $envelope = new RequestEnvelope($origin, $scheme, $handler, $publicFetch);
        $token = new InvocationToken();
        $this->live[$envelope] = ['token' => $token, 'context' => $context];
        return [$envelope, $token];
    }
    public function assert(
        mixed $envelope,
        mixed $token,
        mixed $context,
        RequestPolicyContext $expected
    ): RequestEnvelope
    {
        if (!$envelope instanceof RequestEnvelope || !$token instanceof InvocationToken || $context !== $expected || !isset($this->live[$envelope]) || $this->live[$envelope]['token'] !== $token || $this->live[$envelope]['context'] !== $expected) {
            throw new PolicyException('grant_invalid');
        }
        return $envelope;
    }
    public function close(RequestEnvelope $envelope): void
    {
        unset($this->live[$envelope]);
    }
    public function activeCount(): int
    {
        return count($this->live);
    }
}
