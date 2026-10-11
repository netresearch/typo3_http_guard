<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use Netresearch\HttpGuard\PolicyException;
use Netresearch\NrHttpGuard\Http\GuardedRequestFactory13;
use Netresearch\NrHttpGuard\Http\GuardedRequestFactory14;
use Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility;
use Netresearch\NrHttpGuard\Tests\Fixtures\AbiShapeVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;

/** Fixed named alias shapes in PHPUnit children preserve Infection's active autoloader. */
final class RequestFactoryAbiContractTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[DataProvider('shapes')]
    public function testStructuralAbiBeforeReplacementAutoload(
        string $shape,
        string $version,
        bool $supported,
    ): void {
        self::assertFalse(class_exists(RequestFactory::class, false));
        self::assertFalse(class_exists(Typo3Version::class, false));
        require_once __DIR__ . '/../Fixtures/RequestFactoryAbiShapes.php';
        AbiShapeVersion::$version = $version;
        self::assertTrue(class_alias(AbiShapeVersion::class, Typo3Version::class));
        self::assertTrue(class_alias('Netresearch\NrHttpGuard\Tests\Fixtures\AbiShape' . $shape, RequestFactory::class));
        if (!$supported) {
            try {
                RequestFactoryCompatibility::replacementClass();
                self::fail('An incompatible parent must fail before replacement loading');
            } catch (PolicyException $failure) {
                self::assertSame('transport_unsupported', $failure->reasonCode());
            }
        } else {
            self::assertSame(
                str_starts_with(ltrim($version, 'v'), '13.') ? GuardedRequestFactory13::class : GuardedRequestFactory14::class,
                RequestFactoryCompatibility::replacementClass(),
            );
        }
        self::assertFalse(class_exists(GuardedRequestFactory13::class, false));
        self::assertFalse(class_exists(GuardedRequestFactory14::class, false));
    }

    /** @return iterable<string,array{string,string,bool}> */
    public static function shapes(): iterable
    {
        yield 'Accepted13' => ['Accepted13', '13.4.36', true];
        yield 'Accepted14' => ['Accepted14', '14.3.8', true];
        yield 'FinalParent' => ['FinalParent', '14.3.8', false];
        yield 'AbstractParent' => ['AbstractParent', '14.3.8', false];
        yield 'WrongReadonly' => ['WrongReadonly', '14.3.8', false];
        yield 'NoRequest' => ['NoRequest', '14.3.8', false];
        yield 'ProtectedRequest' => ['ProtectedRequest', '14.3.8', false];
        yield 'FinalRequest' => ['FinalRequest', '14.3.8', false];
        yield 'StaticRequest' => ['StaticRequest', '14.3.8', false];
        yield 'ReferenceReturn' => ['ReferenceReturn', '14.3.8', false];
        yield 'NullableReturn' => ['NullableReturn', '14.3.8', false];
        yield 'WrongReturn' => ['WrongReturn', '14.3.8', false];
        yield 'MissingParameter' => ['MissingParameter', '14.3.8', false];
        yield 'ParameterName' => ['ParameterName', '14.3.8', false];
        yield 'ParameterType' => ['ParameterType', '14.3.8', false];
        yield 'RequiredMethod' => ['RequiredMethod', '14.3.8', false];
        yield 'DefaultMethod' => ['DefaultMethod', '14.3.8', false];
        yield 'OptionalUri' => ['OptionalUri', '14.3.8', false];
        yield 'VariadicContext' => ['VariadicContext', '14.3.8', false];
        yield 'ReferenceOptions' => ['ReferenceOptions', '14.3.8', false];
        yield 'NonNullableContext' => ['NonNullableContext', '14.3.8', false];
        yield 'NoConstructor' => ['NoConstructor', '14.3.8', false];
        yield 'ProtectedConstructor' => ['ProtectedConstructor', '14.3.8', false];
        yield 'FinalConstructor' => ['FinalConstructor', '14.3.8', false];
        yield 'NoFactoryParameter' => ['NoFactoryParameter', '14.3.8', false];
        yield 'FactoryType' => ['FactoryType', '14.3.8', false];
        yield 'ReferenceFactory' => ['ReferenceFactory', '14.3.8', false];
        yield 'VariadicFactory' => ['VariadicFactory', '14.3.8', false];
        yield 'RequiredExtraConstructor' => ['RequiredExtraConstructor', '14.3.8', false];
        yield 'OptionalExtraConstructor' => ['OptionalExtraConstructor', '14.3.8', true];
        yield 'version below semantic floor' => ['Accepted14', '14.3.7', false];
        yield 'unknown next major' => ['Accepted14', '15.0.0', false];
        yield 'readonly mismatch Core13' => ['Accepted14', '13.4.36', false];
        yield 'tag-prefixed13' => ['Accepted13', 'v13.4.36', true];
        yield 'tag-prefixed14' => ['Accepted14', 'v14.3.8', true];
    }

    #[DataProvider('nonReleaseVersions')]
    public function testOnlyCompleteReleaseVersionLexemesAreSupported(string $version): void
    {
        self::assertFalse(RequestFactoryCompatibility::supportsVersion($version));
    }

    /** @return iterable<string,array{string}> */
    public static function nonReleaseVersions(): iterable
    {
        yield 'build suffix' => ['14.3.8+metadata'];
        yield 'four components' => ['14.3.8.0'];
        yield 'release candidate' => ['14.3.9-RC1'];
        yield 'leading whitespace' => [' 14.3.8'];
        yield 'trailing newline' => ["14.3.8\n"];
    }
}
