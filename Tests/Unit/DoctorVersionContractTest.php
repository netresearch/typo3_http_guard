<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use Netresearch\NrHttpGuard\Tests\Fixtures\AbiDoctorUnknownSdkInterface;
use Netresearch\NrHttpGuard\Tests\Fixtures\AbiShapeVersion;
use Netresearch\NrHttpGuard\Tests\Fixtures\AdapterRuntimeFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;

/** A fixed version double models drift after otherwise real installed Core composition. */
final class DoctorVersionContractTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[DataProvider('versionDrifts')]
    public function testDoctorDetectsUnsupportedVersionAfterSuccessfulRegistration(bool $nextMajor): void
    {
        self::assertFalse(class_exists(Typo3Version::class, false));
        require_once __DIR__ . '/../Fixtures/RequestFactoryAbiShapes.php';
        $readonly                 = (new ReflectionClass(RequestFactory::class))->isReadOnly();
        AbiShapeVersion::$version = $readonly ? '14.3.8' : '13.4.36';
        self::assertTrue(class_alias(AbiShapeVersion::class, Typo3Version::class));
        $fixture = new AdapterRuntimeFixture();
        try {
            AbiShapeVersion::$version = $nextMajor ? '15.0.0' : ($readonly ? '14.3.7' : '13.4.35');
            $doctor                   = $fixture->diagnostics->doctor();
            self::assertSame(3, $doctor->exitCode);
            self::assertSame('transport_unsupported', $doctor->payload['reasonCode']);
            self::assertFalse($doctor->payload['protected']);
            self::assertFalse($doctor->payload['registryValid']);
            self::assertSame(AbiShapeVersion::$version, $doctor->payload['versions']['typo3/cms-core']);
        } finally {
            $fixture->restore();
        }
    }

    /** @return iterable<string,array{bool}> */
    public static function versionDrifts(): iterable
    {
        yield 'below semantic floor after boot' => [false];
        yield 'future unsupported major after boot' => [true];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testDoctorReportsUnsupportedSdkAfterValidCoreRegistration(): void
    {
        self::assertFalse(interface_exists(\GuzzleHttp\ClientInterface::class, false));
        require_once __DIR__ . '/../Fixtures/RequestFactoryAbiShapes.php';
        self::assertTrue(class_alias(AbiDoctorUnknownSdkInterface::class, \GuzzleHttp\ClientInterface::class));
        $fixture = new AdapterRuntimeFixture();
        try {
            $doctor = $fixture->diagnostics->doctor();
            self::assertSame(3, $doctor->exitCode);
            self::assertSame('transport_unsupported', $doctor->payload['reasonCode']);
            self::assertFalse($doctor->payload['protected']);
            self::assertTrue($doctor->payload['registryValid']);
        } finally {
            $fixture->restore();
        }
    }
}
