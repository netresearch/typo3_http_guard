<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use Netresearch\NrHttpGuard\Diagnostics\LegacyConfigurationInventory;
use PHPUnit\Framework\TestCase;

final class LegacyConfigurationInventoryTest extends TestCase
{
    public function testMixedLegacyShapesAreInventoriedWithoutNewPolicyOrSecrets(): void
    {
        $secret = 'fake-query-secret-do-not-expose';
        $input  = [
            'allowed_hosts' => ['http://name:' . $secret . '@private.test', 'context' => ['inside.test', false], 'broken' => 42],
        ];
        $before = $input;
        $report = (new LegacyConfigurationInventory())->inspect($input);
        self::assertSame($before, $input);
        self::assertSame(0, $report['automaticGrantsCreated']);
        self::assertSame(1, $report['sources'][0]['entries']);
        self::assertSame(1, $report['sources'][1]['contexts']);
        self::assertSame(1, $report['sources'][1]['entries']);
        self::assertSame(2, $report['invalidEntries']);
        self::assertFalse($report['policyChanged']);
        self::assertStringNotContainsString($secret, json_encode($report, JSON_THROW_ON_ERROR));
        self::assertContains('methods', $report['missingLegacyFields']);
    }
}
