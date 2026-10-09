<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Fuzz;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Throwable;

final class OfflineInputsTest extends TestCase
{
    #[DataProvider('seeds')]
    public function testPersistedSeedsExerciseOfflineProperties(int $seed): void
    {
        $properties = new OfflineProperties();
        $random     = new Randomizer(new Mt19937($seed));
        for ($case = 0; $case < 1000; ++$case) {
            $length = $random->getInt(1, 512);
            $input  = chr($case % 6) . $random->getBytes($length);
            try {
                $properties->exercise($input);
            } catch (Throwable $error) {
                self::fail(
                    'Offline property failed at seed ' . $seed . ', case ' . $case . ', input sha256 ' . hash('sha256', $input) . ': ' . $error->getMessage(),
                );
            }
        }
        self::assertTrue(true);
    }

    public function testCommittedCorpusReplays(): void
    {
        $properties = new OfflineProperties();
        $paths      = glob(__DIR__ . '/corpus/*');
        self::assertIsArray($paths);
        self::assertNotEmpty($paths);
        foreach ($paths as $path) {
            $input = file_get_contents($path);
            self::assertIsString($input);
            $properties->exercise($input);
        }
    }

    /** @return iterable<string, array{int}> */
    public static function seeds(): iterable
    {
        $data = json_decode((string) file_get_contents(__DIR__ . '/seeds.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($data['seeds'] as $seed) {
            yield 'seed-' . $seed => [$seed];
        }
    }
}
