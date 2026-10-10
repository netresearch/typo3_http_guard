<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures;

use GuzzleHttp\Handler\EasyHandle;
use Psr\Http\Message\RequestInterface;

/** Fixed ABI doubles for isolated unit tests. None of these opens a native handle. */
interface FactoryContractValid
{
    public function create(RequestInterface $request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}

interface FactoryContractEmpty {}
interface FactoryContractExtra extends FactoryContractValid
{
    public function reset(): void;
}
interface FactoryContractStatic
{
    public static function create(RequestInterface $request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractReferenceReturn
{
    public function &create(RequestInterface $request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractNullableReturn
{
    public function create(RequestInterface $request, array $options): ?EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractUnionReturn
{
    public function create(RequestInterface $request, array $options): EasyHandle|false;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractMissingReturn
{
    public function create(RequestInterface $request, array $options);

    public function release(EasyHandle $easy): void;
}
interface FactoryContractReferenceParameter
{
    public function create(RequestInterface &$request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractVariadicParameter
{
    public function create(RequestInterface $request, array ...$options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractNullableParameter
{
    public function create(?RequestInterface $request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractMixedParameter
{
    public function create(mixed $request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractUnionParameter
{
    public function create(RequestInterface|false $request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractMissingParameterType
{
    public function create($request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractOptionalParameter
{
    public function create(RequestInterface $request, array $options = []): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractAdditionalParameter
{
    public function create(RequestInterface $request, array $options, int $additional = 0): EasyHandle;

    public function release(EasyHandle $easy): void;
}
interface FactoryContractWrongRelease
{
    public function create(RequestInterface $request, array $options): EasyHandle;

    public function release(EasyHandle $easy): bool;
}
interface FactoryContractRenamedCreate
{
    public function construct(RequestInterface $request, array $options): EasyHandle;

    public function release(EasyHandle $easy): void;
}
abstract class FactoryContractConcrete
{
    abstract public function create(RequestInterface $request, array $options): EasyHandle;

    abstract public function release(EasyHandle $easy): void;
}

class FactoryConstructorValid
{
    public function __construct(int $maximum) {}
}
class FactoryConstructorMissingArgument
{
    public function __construct() {}
}
class FactoryConstructorMandatoryExtra
{
    public function __construct(int $maximum, int $extra) {}
}
class FactoryConstructorPrivate
{
    private function __construct(int $maximum) {}
}
class FactoryConstructorVariadic
{
    public function __construct(int ...$maximum) {}
}

class MultiContractValid
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed $request, array $options): void {}

    public function tick(): void {}

    public function close(): void {}

    public function __destruct() {}
}
class MultiContractMissingArgument extends MultiContractValid
{
    public function __construct() {}
}
class MultiContractMandatoryExtra extends MultiContractValid
{
    public function __construct(array $options, int $extra) {}
}
class MultiContractOptionalExtra extends MultiContractValid
{
    public function __construct(array $options = [], int $extra = 0) {}
}
class MultiContractPrivateConstructor extends MultiContractValid
{
    private function __construct(array $options = []) {}
}
class MultiContractShortInvocation
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed $request): void {}

    public function tick(): void {}

    public function close(): void {}

    public function __destruct() {}
}
class MultiContractVariadicInvocation
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed ...$arguments): void {}

    public function tick(): void {}

    public function close(): void {}

    public function __destruct() {}
}
class MultiContractPrivateTick
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed $request, array $options): void {}

    private function tick(): void {}

    public function close(): void {}

    public function __destruct() {}
}
class MultiContractStaticTick
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed $request, array $options): void {}

    public static function tick(): void {}

    public function close(): void {}

    public function __destruct() {}
}
class MultiContractMandatoryTick
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed $request, array $options): void {}

    public function tick(int $extra): void {}

    public function close(): void {}

    public function __destruct() {}
}
class MultiContractMissingCleanup
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed $request, array $options): void {}

    public function tick(): void {}
}
class MultiContractCloseOnly
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed $request, array $options): void {}

    public function tick(): void {}

    public function close(): void {}
}
class MultiContractDestructorOnly
{
    public function __construct(array $options = []) {}

    public function __invoke(mixed $request, array $options): void {}

    public function tick(): void {}

    public function __destruct() {}
}

class StackContractNonarray
{
    private mixed $stack = null;

    public function push(callable $middleware, string $name = ''): void {}
}
class StackContractAssociative
{
    private array $stack = [];

    public function push(callable $middleware, string $name = ''): void
    {
        $this->stack['item'] = [$middleware, $name];
    }
}
class StackContractWrongEntry
{
    private array $stack = [];

    public function push(callable $middleware, string $name = ''): void
    {
        $this->stack[] = ['handler' => $middleware, 'name' => $name];
    }
}
class StackContractMissingInventory
{
    private array $middlewares = [];

    public function push(callable $middleware, string $name = ''): void
    {
        $this->middlewares[] = [$middleware, $name];
    }
}
