<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Http;

use Netresearch\HttpGuard\PolicyException;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;

/** Supported Core branches with structural parent ABI validation. */
final class RequestFactoryCompatibility
{
    public static function replacementClass(): string
    {
        self::assertSupported();

        return (new ReflectionClass(RequestFactory::class))->isReadOnly() ? GuardedRequestFactory14::class : GuardedRequestFactory13::class;
    }

    public static function assertSupported(): void
    {
        $parent  = new ReflectionClass(RequestFactory::class);
        $version = ltrim((new Typo3Version())->getVersion(), 'v');
        if (!self::supportsVersion($version) || $parent->isFinal() || $parent->isAbstract() || !$parent->hasMethod('request') || $parent->isReadOnly() !== str_starts_with($version, '14.')) {
            throw new PolicyException('transport_unsupported');
        }
        $method     = $parent->getMethod('request');
        $parameters = $method->getParameters();
        if (!$method->isPublic() || $method->isFinal() || $method->isStatic() || $method->returnsReference() || (string) $method->getReturnType() !== ResponseInterface::class || count($parameters) !== 4) {
            throw new PolicyException('transport_unsupported');
        }
        $expected = [
            ['uri', 'string', false, null],
            ['method', 'string', true, 'GET'],
            ['options', 'array', true, []],
            ['context', '?string', true, null],
        ];
        foreach ($parameters as $index => $parameter) {
            [$name, $type, $optional, $default] = $expected[$index];
            if ($parameter->getName() !== $name || (string) $parameter->getType() !== $type || $parameter->isOptional() !== $optional || $parameter->isVariadic() || $parameter->isPassedByReference() || $optional && $parameter->getDefaultValue() !== $default) {
                throw new PolicyException('transport_unsupported');
            }
        }
        $constructor = $parent->getConstructor();
        $arguments   = $constructor?->getParameters() ?? [];
        if ($constructor === null || !$constructor->isPublic() || $constructor->isFinal() || $arguments === [] || (string) $arguments[0]->getType() !== GuzzleClientFactory::class || $arguments[0]->isPassedByReference() || $arguments[0]->isVariadic()) {
            throw new PolicyException('transport_unsupported');
        }
        foreach (array_slice($arguments, 1) as $argument) {
            if (!$argument->isOptional()) {
                throw new PolicyException('transport_unsupported');
            }
        }
    }

    public static function supportsVersion(string $version): bool
    {
        if (preg_match('/\Av?\d+\.\d+\.\d+\z/', $version) !== 1) {
            return false;
        }
        $version = ltrim($version, 'v');

        return version_compare($version, '13.4.36', '>=') && version_compare($version, '14.0.0', '<') || version_compare($version, '14.3.8', '>=') && version_compare($version, '15.0.0', '<');
    }
}
