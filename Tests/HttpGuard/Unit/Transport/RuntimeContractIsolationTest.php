<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Transport;

use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Tests\Unit\Transport\Fixtures as Shapes;
use Netresearch\HttpGuard\Transport\RuntimeSupport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class RuntimeContractIsolationTest extends TestCase
{
    /** PHPUnit forwards its Infection bootstrap into the isolated process. These doubles test only ABI rejection, not a real SDK. */
    #[DataProvider('runtimeApiShapes')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRuntimeApiDiscriminatorsFailClosedBeforeNativeConstruction(
        array $replacements,
        bool $supported,
    ): void {
        require_once __DIR__ . '/Fixtures/RuntimeContractShapes.php';
        foreach ($replacements as $target => $replacement) {
            self::assertFalse(
                class_exists($target, false) || interface_exists($target, false),
                'The ABI double must precede SDK autoload',
            );
            self::assertTrue(class_alias($replacement, $target));
        }
        if (!$supported) {
            $this->expectException(PolicyException::class);
            $this->expectExceptionMessage('transport_unsupported');
        }
        RuntimeSupport::assertSupported();
        if ($supported) {
            self::assertContains(RuntimeSupport::major(), [7, 8]);
        }
    }

    public static function runtimeApiShapes(): iterable
    {
        $factory     = 'GuzzleHttp\Handler\CurlFactoryInterface';
        $multi       = 'GuzzleHttp\Handler\CurlMultiHandler';
        $constructor = 'GuzzleHttp\Handler\CurlFactory';
        $stack       = 'GuzzleHttp\HandlerStack';
        yield 'unmodified installed contracts' => [[], true];
        yield 'only the selected SDK cleanup contract' => [
            [
                $multi => RuntimeSupport::major() === 8 ? Shapes\MultiContractCloseOnly::class : Shapes\MultiContractDestructorOnly::class,
            ],
            true,
        ];
        yield 'valid factory interface discriminator' => [[$factory => Shapes\FactoryContractValid::class], true];
        foreach ([
            Shapes\FactoryContractEmpty::class,
            Shapes\FactoryContractExtra::class,
            Shapes\FactoryContractStatic::class,
            Shapes\FactoryContractReferenceReturn::class,
            Shapes\FactoryContractNullableReturn::class,
            Shapes\FactoryContractUnionReturn::class,
            Shapes\FactoryContractMissingReturn::class,
            Shapes\FactoryContractReferenceParameter::class,
            Shapes\FactoryContractVariadicParameter::class,
            Shapes\FactoryContractNullableParameter::class,
            Shapes\FactoryContractMixedParameter::class,
            Shapes\FactoryContractUnionParameter::class,
            Shapes\FactoryContractMissingParameterType::class,
            Shapes\FactoryContractOptionalParameter::class,
            Shapes\FactoryContractAdditionalParameter::class,
            Shapes\FactoryContractWrongRelease::class,
            Shapes\FactoryContractRenamedCreate::class,
            Shapes\FactoryContractConcrete::class,
        ] as $shape) {
            yield $shape => [[$factory => $shape], false];
        }
        foreach ([Shapes\FactoryConstructorValid::class, Shapes\FactoryConstructorVariadic::class] as $shape) {
            yield $shape => [[$constructor => $shape], true];
        }
        foreach ([
            Shapes\FactoryConstructorMissingArgument::class,
            Shapes\FactoryConstructorMandatoryExtra::class,
            Shapes\FactoryConstructorPrivate::class,
        ] as $shape) {
            yield $shape => [[$constructor => $shape], false];
        }
        foreach ([
            Shapes\MultiContractValid::class,
            Shapes\MultiContractOptionalExtra::class,
            Shapes\MultiContractVariadicInvocation::class,
        ] as $shape) {
            yield $shape => [[$multi => $shape], true];
        }
        foreach ([
            Shapes\MultiContractMissingArgument::class,
            Shapes\MultiContractMandatoryExtra::class,
            Shapes\MultiContractPrivateConstructor::class,
            Shapes\MultiContractShortInvocation::class,
            Shapes\MultiContractPrivateTick::class,
            Shapes\MultiContractStaticTick::class,
            Shapes\MultiContractMandatoryTick::class,
            Shapes\MultiContractMissingCleanup::class,
        ] as $shape) {
            yield $shape => [[$multi => $shape], false];
        }
        foreach ([
            Shapes\StackContractNonarray::class,
            Shapes\StackContractAssociative::class,
            Shapes\StackContractWrongEntry::class,
            Shapes\StackContractMissingInventory::class,
        ] as $shape) {
            yield $shape => [[$stack => $shape], false];
        }
    }
}
