<?php

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use DateTimeImmutable;
use LogicException;
use Netresearch\HttpGuard\ClockInterface;
use Netresearch\NrHttpGuard\Configuration\ConfigurationLoader;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticBootState;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticsService;
use Netresearch\NrHttpGuard\Diagnostics\LegacyConfigurationInventory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DiagnosticsServiceTest extends TestCase
{
    private mixed $original;
    private array $proxyEnvironment = [];

    protected function setUp(): void
    {
        $this->original             = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        foreach (getenv(null, true) ?: [] as $key => $value) {
            if (in_array(strtolower((string) $key), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                $this->proxyEnvironment[$key] = $value;
                putenv($key);
            }
        }
    }

    protected function tearDown(): void
    {
        if ($this->original === null) {
            unset($GLOBALS['TYPO3_CONF_VARS']);
        } else {
            $GLOBALS['TYPO3_CONF_VARS'] = $this->original;
        }
        foreach (getenv(null, true) ?: [] as $key => $value) {
            if (in_array(strtolower((string) $key), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                putenv($key);
            }
        }
        foreach ($this->proxyEnvironment as $key => $value) {
            putenv($key . '=' . $value);
        }
    }

    private function service(): DiagnosticsService
    {
        $clock = new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-10-09T12:00:00Z');
            }

            public function monotonic(): float
            {
                return 0.0;
            }
        };

        return new DiagnosticsService(
            new ConfigurationLoader(),
            new DiagnosticBootState(),
            new LegacyConfigurationInventory(),
            static fn () => new class {
                public function assertValid(): void {}
            },
            static fn () => throw new LogicException('Forbidden raw syntax reached policy engine'),
            static fn () => throw new LogicException('Forbidden raw syntax reached registry'),
            $clock,
        );
    }

    #[DataProvider('forbiddenRawTargets')]
    public function testRawSyntaxRejectsBeforePsr7CanEraseIt(string $url): void
    {
        $result = $this->service()->policyCheck($url, noDns: true);
        self::assertSame(2, $result->exitCode);
        self::assertSame('invalid_target', $result->payload['reasonCode']);
        self::assertFalse($result->payload['httpSent']);
    }

    public static function forbiddenRawTargets(): iterable
    {
        yield 'empty fragment' => ['http://guard.test:8080/a#'];
        yield 'nonempty fragment' => ['http://guard.test:8080/a#fragment'];
        yield 'backslash' => ['http://guard.test:8080/a\b'];
    }

    public function testOverdueReviewWarnsWithoutDisablingConfiguredProfile(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard'] = [
            'endpoints' => [
                'past-review' => [
                    'origin'       => 'https://erp.test',
                    'allowedCidrs' => ['10.23.5.12/32'],
                    'methods'      => ['POST'],
                    'purpose'      => 'Synthetic fixture',
                    'owner'        => 'Test maintainers',
                    'reviewAfter'  => '2026-10-08',
                ],
                'future-review' => [
                    'origin'       => 'https://other.test',
                    'allowedCidrs' => ['10.23.5.13/32'],
                    'methods'      => ['POST'],
                    'purpose'      => 'Synthetic fixture',
                    'owner'        => 'Test maintainers',
                    'reviewAfter'  => '2026-10-10',
                ],
            ],
        ];
        $service = $this->service();
        $config  = $service->configCheck();
        self::assertSame(0, $config->exitCode);
        self::assertSame(['past-review'], $config->payload['warnings']['overdueEndpointReviews']);
        self::assertSame(2, $config->payload['endpointCount']);
        $doctor = $service->doctor();
        self::assertSame(0, $doctor->exitCode);
        self::assertTrue($doctor->payload['protected']);
        self::assertSame(['past-review'], $doctor->payload['warnings']['overdueEndpointReviews']);
    }

    public function testDoctorReportsMixedCaseNoProxyAsUnsupportedWithoutValues(): void
    {
        putenv('No_PrOxY=synthetic-secret-proxy-exclusion');
        $doctor = $this->service()->doctor();
        self::assertSame(3, $doctor->exitCode);
        self::assertFalse($doctor->payload['protected']);
        self::assertSame('proxy_unsupported', $doctor->payload['reasonCode']);
        self::assertSame(['No_PrOxY'], $doctor->payload['proxy']['processVariablesPresent']);
        self::assertStringNotContainsString(
            'synthetic-secret-proxy-exclusion',
            json_encode($doctor->payload, JSON_THROW_ON_ERROR),
        );
    }
}
