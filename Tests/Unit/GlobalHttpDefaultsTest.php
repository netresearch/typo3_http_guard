<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use Netresearch\HttpGuard\GuardConfig;
use Netresearch\NrHttpGuard\Configuration\GlobalHttpDefaults;
use PHPUnit\Framework\TestCase;

final class GlobalHttpDefaultsTest extends TestCase
{
    public function testCoreDefaultsAreBoundBeforeClientConstruction(): void
    {
        $http   = ['allow_redirects' => ['max' => 5, 'strict' => false], 'timeout' => 0];
        $result = (new GlobalHttpDefaults())->apply($http, GuardConfig::fromArray(['redirects' => ['max' => 2]]));
        self::assertSame(['max' => 2, 'strict' => false], $result['allow_redirects']);
        self::assertSame(0, $result['timeout']);
    }

    public function testExplicitOperatorLimitIsNotSilentlyClamped(): void
    {
        $http = ['allow_redirects' => ['max' => 7, 'strict' => true]];
        self::assertSame(
            $http,
            (new GlobalHttpDefaults())->apply($http, GuardConfig::fromArray(['redirects' => ['max' => 2]])),
        );
    }

    public function testUnprotectedModesRetainOriginalDefaults(): void
    {
        $http = ['allow_redirects' => ['max' => 5, 'strict' => false]];
        foreach (['observe', 'disabled'] as $mode) {
            self::assertSame(
                $http,
                (new GlobalHttpDefaults())->apply(
                    $http,
                    GuardConfig::fromArray(['mode' => $mode, 'redirects' => ['max' => 0]]),
                ),
            );
        }
    }

    public function testZeroPolicyDefaultDisablesFollowing(): void
    {
        self::assertFalse(
            (new GlobalHttpDefaults())->apply([], GuardConfig::fromArray(['redirects' => ['max' => 0]]))['allow_redirects'],
        );
    }

    public function testLargerPolicyLimitDoesNotWeakenTheCoreDefault(): void
    {
        $http = ['allow_redirects' => ['max' => 5, 'strict' => false]];
        self::assertSame(
            $http,
            (new GlobalHttpDefaults())->apply($http, GuardConfig::fromArray(['redirects' => ['max' => 10]])),
        );
    }
}
