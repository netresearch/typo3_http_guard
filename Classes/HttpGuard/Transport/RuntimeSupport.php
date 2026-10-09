<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\HttpGuard\Transport;

use Composer\InstalledVersions;
use GuzzleHttp\ClientInterface;
use Netresearch\HttpGuard\PolicyException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Throwable;

final class RuntimeSupport
{
    public static function assertSupported(): void
    {
        if (!extension_loaded('curl') || !function_exists('curl_multi_exec') || !defined('CURLOPT_RESOLVE') || !defined('CURLOPT_FRESH_CONNECT') || !defined('CURLOPT_FORBID_REUSE') || !class_exists(InstalledVersions::class)) {
            throw new PolicyException('transport_unsupported');
        }
        $curl = curl_version();
        if (($curl['version_number'] ?? 0) < 0x73B00) {
            throw new PolicyException('transport_unsupported');
        }
        $expected = match (self::major()) {
            7 => [
                'guzzlehttp/guzzle'   => [7, '7.15.2'],
                'guzzlehttp/promises' => [2, '2.5.1'],
                'guzzlehttp/psr7'     => [2, '2.13.0'],
            ],
            8 => [
                'guzzlehttp/guzzle'   => [8, '8.2.0'],
                'guzzlehttp/promises' => [3, '3.0.2'],
                'guzzlehttp/psr7'     => [3, '3.1.0'],
            ],
            default => throw new PolicyException('transport_unsupported'),
        };
        foreach ($expected as $package => [$major, $minimum]) {
            if (!InstalledVersions::isInstalled($package)) {
                throw new PolicyException('transport_unsupported');
            }
            $version = ltrim((string) InstalledVersions::getPrettyVersion($package), 'v');
            if (preg_match('/^(\d+)\.\d+\.\d+(?:[.+-][0-9A-Za-z.-]+)?$/D', $version, $parts) !== 1 || (int) $parts[1] !== $major || version_compare($version, $minimum, '<')) {
                throw new PolicyException('transport_unsupported');
            }
        }
        self::assertTransportContracts();
    }

    /** Check the APIs used by the transport without constructing native handles. */
    private static function assertTransportContracts(): void
    {
        $cleanup = self::major() === 8 ? 'close' : '__destruct';
        try {
            self::assertFactoryInterface();
            foreach ([
                \GuzzleHttp\Handler\CurlFactory::class      => ['__construct' => 1],
                \GuzzleHttp\Handler\CurlMultiHandler::class => ['__construct' => 1, '__invoke' => 2, 'tick' => 0, $cleanup => 0],
            ] as $class => $calls) {
                $reflection = new ReflectionClass($class);
                foreach ($calls as $name => $arguments) {
                    $method = $reflection->getMethod($name);
                    if (!$method->isPublic() || $method->isStatic() || $method->getNumberOfRequiredParameters() > $arguments || $method->getNumberOfParameters() < $arguments && !$method->isVariadic()) {
                        throw new PolicyException('transport_unsupported');
                    }
                }
            }

            // GuardedClientFactory reads this private inventory to enforce ordering.
            $stack = new \GuzzleHttp\HandlerStack();
            $probe = static fn (callable $handler): callable => $handler;
            $stack->push($probe, 'runtime_contract_probe');
            $entries = (new ReflectionProperty(\GuzzleHttp\HandlerStack::class, 'stack'))->getValue($stack);
            if (!is_array($entries) || !array_is_list($entries) || $entries !== [[$probe, 'runtime_contract_probe']]) {
                throw new PolicyException('transport_unsupported');
            }
        } catch (Throwable) {
            throw new PolicyException('transport_unsupported');
        }
    }

    public static function major(): int
    {
        $constant = ClientInterface::class . '::MAJOR_VERSION';
        if (!defined($constant)) {
            throw new PolicyException('transport_unsupported');
        }

        return constant($constant);
    }

    /** Reject an incompatible public factory interface before the decorator autoloads. */
    private static function assertFactoryInterface(): void
    {
        $interface = new ReflectionClass(\GuzzleHttp\Handler\CurlFactoryInterface::class);
        if (!$interface->isInterface() || count($interface->getMethods()) !== 2) {
            throw new PolicyException('transport_unsupported');
        }
        foreach ([
            'create'  => [[\Psr\Http\Message\RequestInterface::class, 'array'], \GuzzleHttp\Handler\EasyHandle::class],
            'release' => [[\GuzzleHttp\Handler\EasyHandle::class], 'void'],
        ] as $name => [$types, $return]) {
            $method     = $interface->getMethod($name);
            $returnType = $method->getReturnType();
            if (!$method->isPublic() || $method->isStatic() || $method->returnsReference() || $method->getNumberOfParameters() !== count($types) || $method->getNumberOfRequiredParameters() !== count($types) || !$returnType instanceof ReflectionNamedType || $returnType->getName() !== $return || $returnType->allowsNull()) {
                throw new PolicyException('transport_unsupported');
            }
            foreach ($method->getParameters() as $position => $parameter) {
                $type = $parameter->getType();
                if ($parameter->isPassedByReference() || $parameter->isVariadic() || !$type instanceof ReflectionNamedType || $type->getName() !== $types[$position] || $type->allowsNull()) {
                    throw new PolicyException('transport_unsupported');
                }
            }
        }
    }
}
