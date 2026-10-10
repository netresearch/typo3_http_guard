<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use Netresearch\HttpGuard\PolicyException;
use Netresearch\NrHttpGuard\Configuration\ConfigurationLoader;
use PHPUnit\Framework\TestCase;

final class ConfigurationLoaderTest extends TestCase
{
    private mixed $original;

    protected function setUp(): void
    {
        $this->original = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->original === null) {
            unset($GLOBALS['TYPO3_CONF_VARS']);
        } else {
            $GLOBALS['TYPO3_CONF_VARS'] = $this->original;
        }
    }

    public function testMissingConfigurationHasNoInternalGrantsAndIsAnImmutableSnapshot(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        $loader                     = new ConfigurationLoader();
        $config                     = $loader->load();
        self::assertSame('enforce', $config->mode);
        self::assertSame([], $config->data['endpoints']);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard'] = ['mode' => 'disabled'];
        self::assertSame($config, $loader->load());
        self::assertSame('enforce', $loader->load()->mode);
    }

    public function testWrongDeployedTypeFailsClosed(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard'] = 'disabled';
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        (new ConfigurationLoader())->load();
    }

    public function testUnknownFieldsAreConfigurationErrors(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard'] = ['allowPrivate' => true];
        $this->expectException(PolicyException::class);
        (new ConfigurationLoader())->load();
    }
}
