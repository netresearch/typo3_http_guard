<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Transport;

use GuzzleHttp\Handler\CurlFactoryInterface;
use GuzzleHttp\Handler\EasyHandle;
use Netresearch\HttpGuard\PolicyException;
use Psr\Http\Message\RequestInterface;

/** A lease can allocate one native handle, including during SDK retry or preparation callbacks. */
final class SingleUseCurlFactory implements CurlFactoryInterface
{
    private bool $created = false;

    public function __construct(private readonly CurlFactoryInterface $factory) {}

    /** @param array<array-key,mixed> $options */
    public function create(RequestInterface $request, array $options): EasyHandle
    {
        if ($this->created) {
            throw new PolicyException('transport_unsupported');
        }
        // Consume the attempt before body preparation can reenter or fail.
        $this->created = true;

        return $this->factory->create($request, $options);
    }

    public function release(EasyHandle $easy): void
    {
        $this->factory->release($easy);
    }
}
