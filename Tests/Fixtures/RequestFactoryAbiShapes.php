<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Fixtures;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

/** Named, isolated structural probes; these are not installed TYPO3 versions. */
final class AbiShapeVersion
{
    public static string $version = '14.3.8';

    public function getVersion(): string
    {
        return self::$version;
    }
}
/** A fixed unsupported SDK-major interface; no SDK client is constructed in this child. */
interface AbiDoctorUnknownSdkInterface
{
    public const MAJOR_VERSION = 9;
}
class AbiShapeAccepted13
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeAccepted14
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

final readonly class AbiShapeFinalParent
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

abstract readonly class AbiShapeAbstractParent
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

class AbiShapeWrongReadonly
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeNoRequest
{
    public function __construct(GuzzleClientFactory $clientFactory) {}
}

readonly class AbiShapeProtectedRequest
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    protected function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeFinalRequest
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    final public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeStaticRequest
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public static function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeReferenceReturn
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function &request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeNullableReturn
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ?ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeWrongReturn
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(string $uri, string $method = 'GET', array $options = [], ?string $context = null): string
    {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeMissingParameter
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(string $uri, string $method = 'GET', array $options = []): ResponseInterface
    {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeParameterName
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $verb = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeParameterType
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        UriInterface $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeRequiredMethod
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method,
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeDefaultMethod
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'POST',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeOptionalUri
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri = '',
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeVariadicContext
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string ...$context,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeReferenceOptions
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array &$options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeNonNullableContext
{
    public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        string $context = '',
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeNoConstructor
{
    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeProtectedConstructor
{
    protected function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeFinalConstructor
{
    final public function __construct(GuzzleClientFactory $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeNoFactoryParameter
{
    public function __construct() {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeFactoryType
{
    public function __construct(object $clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeReferenceFactory
{
    public function __construct(GuzzleClientFactory &$clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeVariadicFactory
{
    public function __construct(GuzzleClientFactory ...$clientFactory) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeRequiredExtraConstructor
{
    public function __construct(GuzzleClientFactory $clientFactory, string $required) {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}

readonly class AbiShapeOptionalExtraConstructor
{
    public function __construct(GuzzleClientFactory $clientFactory, string $optional = '') {}

    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null,
    ): ResponseInterface {
        throw new LogicException('No HTTP is permitted in ABI probes');
    }
}
