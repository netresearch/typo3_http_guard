<?php

declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Http;

use Composer\InstalledVersions;
use Netresearch\HttpGuard\PolicyException;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;

/** Exact parent ABI proven by the genuine four-cell runtime matrix. */
final class RequestFactoryCompatibility
{
    public static function replacementClass(): string
    {
        return (new \ReflectionClass(RequestFactory::class))->isReadOnly() ? GuardedRequestFactory14::class : GuardedRequestFactory13::class;
    }
    public static function assertSupported(): void
    {
        $parent = new \ReflectionClass(RequestFactory::class);
        $version = ltrim(
            (string) InstalledVersions::getPrettyVersion('typo3/cms-core'),
            'v'
        );
        if (!in_array($version, ['13.4.35', '14.3.7'], true) || $parent->isFinal() || $parent->isReadOnly() !== str_starts_with($version, '14.')) {
            throw new PolicyException('transport_unsupported');
        }
        $method = $parent->getMethod('request');
        $parameters = $method->getParameters();
        if (!$method->isPublic() || $method->isFinal() || $method->isStatic() || (string) $method->getReturnType() !== ResponseInterface::class || count($parameters) !== 4) {
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
        $arguments = $constructor?->getParameters() ?? [];
        if (count($arguments) !== 1 || (string) $arguments[0]->getType() !== GuzzleClientFactory::class) {
            throw new PolicyException('transport_unsupported');
        }
    }
}
