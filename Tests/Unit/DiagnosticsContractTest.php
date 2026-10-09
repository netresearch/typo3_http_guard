<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Tests\Unit;

use Composer\InstalledVersions;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Resolution;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\NrHttpGuard\Diagnostics\DiagnosticsService;
use Netresearch\NrHttpGuard\Diagnostics\LegacyConfigurationInventory;
use Netresearch\NrHttpGuard\Tests\Fixtures\AdapterRuntimeFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Information\Typo3Version;

final class DiagnosticsContractTest extends TestCase
{
    private ?AdapterRuntimeFixture $fixture = null;

    protected function tearDown(): void
    {
        $this->fixture?->restore();
    }

    #[DataProvider('modes')]
    public function testDoctorReportsRealVersionsAndExactProtectionScope(
        string $mode,
        int $exit,
        bool $protected,
    ): void {
        $this->fixture = new AdapterRuntimeFixture(['mode' => $mode, 'tls' => ['requireVerification' => true]]);
        $doctor        = $this->fixture->diagnostics->doctor();
        self::assertSame($exit, $doctor->exitCode);
        self::assertSame(
            [
                'eventVersion' => 1,
                'versions'     => [
                    'php'                 => PHP_VERSION,
                    'curl'                => curl_version()['version'],
                    'guzzlehttp/guzzle'   => InstalledVersions::getPrettyVersion('guzzlehttp/guzzle'),
                    'guzzlehttp/promises' => InstalledVersions::getPrettyVersion('guzzlehttp/promises'),
                    'guzzlehttp/psr7'     => InstalledVersions::getPrettyVersion('guzzlehttp/psr7'),
                    'typo3/cms-core'      => (new Typo3Version())->getVersion(),
                ],
                'proxy'    => ['processVariablesPresent' => [], 'serverHttpProxyPresent' => false, 'valuesRedacted' => true],
                'coverage' => [
                    'coreRequestFactoryAfterRegistration' => true,
                    'incomingPsr15'                       => false,
                    'vaultAutomaticallyCovered'           => false,
                    'rawGuzzleAutomaticallyCovered'       => false,
                    'requestHandlerOverrideCovered'       => false,
                    'proxySupported'                      => false,
                    'streamHandlerSupported'              => false,
                    'earlyBootstrapCallsCovered'          => false,
                ],
                'mode'           => $mode,
                'policyRevision' => $this->fixture->config->revision,
                'registryValid'  => true,
                'warnings'       => ['overdueEndpointReviews' => [], 'tlsVerificationPolicyNotRequired' => false],
                'protected'      => $protected,
                'reasonCode'     => null,
            ],
            $doctor->payload,
        );
    }

    /** @return iterable<string,array{string,int,bool}> */
    public static function modes(): iterable
    {
        yield 'enforce protects' => ['enforce', 0, true];
        yield 'observe is diagnostic' => ['observe', 2, false];
        yield 'disabled delegates' => ['disabled', 2, false];
    }

    #[DataProvider('httpProxyValues')]
    public function testDoctorDistinguishesNeutralAndPresentCoreProxyValues(mixed $proxy, int $exit): void
    {
        $this->fixture = new AdapterRuntimeFixture([], ['proxy' => $proxy]);
        $result        = $this->fixture->diagnostics->doctor();
        self::assertSame($exit, $result->exitCode);
        self::assertSame($exit === 0, $result->payload['protected']);
        self::assertSame($exit === 0 ? null : 'proxy_unsupported', $result->payload['reasonCode']);
        self::assertStringNotContainsString(
            'synthetic-proxy-private',
            json_encode($result->payload, JSON_THROW_ON_ERROR),
        );
    }

    /** @return iterable<string,array{mixed,int}> */
    public static function httpProxyValues(): iterable
    {
        yield 'absent null' => [null, 0];
        yield 'empty string' => ['', 0];
        yield 'false' => [false, 0];
        yield 'empty map' => [[], 0];
        yield 'zero is not false' => [0, 3];
        yield 'true' => [true, 3];
        yield 'configured url' => ['http://synthetic-proxy-private.invalid', 3];
    }

    public function testServerProxyPresenceIsReportedWithoutTreatingAnIncomingHeaderAsProcessProxy(): void
    {
        $this->fixture         = new AdapterRuntimeFixture();
        $_SERVER['HTTP_PROXY'] = 'incoming-secret-header';
        $result                = $this->fixture->diagnostics->doctor();
        self::assertSame(0, $result->exitCode);
        self::assertSame([], $result->payload['proxy']['processVariablesPresent']);
        self::assertTrue($result->payload['proxy']['serverHttpProxyPresent']);
        self::assertTrue($result->payload['proxy']['valuesRedacted']);
        self::assertStringNotContainsString(
            'incoming-secret-header',
            json_encode($result->payload, JSON_THROW_ON_ERROR),
        );
    }

    public function testMissingRegisteredMiddlewareProducesConfigurationFailure(): void
    {
        $this->fixture = new AdapterRuntimeFixture(register: false);
        $result        = $this->fixture->diagnostics->doctor();
        self::assertSame(3, $result->exitCode);
        self::assertFalse($result->payload['registryValid']);
        self::assertFalse($result->payload['protected']);
        self::assertSame('configuration_invalid', $result->payload['reasonCode']);
    }

    public function testConfigCheckKeepsTodayReviewBoundaryAndTlsWarning(): void
    {
        $this->fixture = new AdapterRuntimeFixture(
            [
                'tls'       => ['requireVerification' => false],
                'endpoints' => [
                    'past'        => AdapterRuntimeFixture::endpoint('2026-10-08'),
                    'today'       => AdapterRuntimeFixture::endpoint('2026-10-09'),
                    'future'      => AdapterRuntimeFixture::endpoint('2026-10-10'),
                    'unscheduled' => AdapterRuntimeFixture::endpoint(),
                ],
            ],
        );
        $result = $this->fixture->diagnostics->configCheck();
        self::assertSame(0, $result->exitCode);
        self::assertSame(
            [
                'schemaVersion'  => 1,
                'mode'           => 'enforce',
                'policyRevision' => $this->fixture->config->revision,
                'endpointCount'  => 4,
                'warnings'       => ['overdueEndpointReviews' => ['past'], 'tlsVerificationPolicyNotRequired' => true],
                'httpSent'       => false,
            ],
            $result->payload,
        );
    }

    #[DataProvider('policyCases')]
    public function testPolicyCheckUsesRealOfflineRegistryAndMapsDiagnosticExitCodes(
        string $url,
        ?string $endpoint,
        int $exit,
        string $decision,
        ?string $reason,
        ?string $profile,
    ): void {
        $this->fixture = new AdapterRuntimeFixture(['endpoints' => ['erp' => AdapterRuntimeFixture::endpoint()]]);
        $result        = $this->fixture->diagnostics->policyCheck($url, $endpoint, true);
        self::assertSame($exit, $result->exitCode);
        self::assertSame($decision, $result->payload['decision']);
        self::assertSame($reason, $result->payload['reasonCode']);
        self::assertFalse($result->payload['httpSent']);
        self::assertTrue($result->payload['diagnosticOnly']);
        if (!in_array($reason, ['grant_invalid', 'resolution_unverified'], true)) {
            self::assertSame($profile, $result->payload['profileId']);
            self::assertSame('enforce', $result->payload['mode']);
            self::assertSame($this->fixture->config->revision, $result->payload['policyRevision']);
        }
        self::assertStringNotContainsString(
            'synthetic-private-query',
            json_encode($result->payload, JSON_THROW_ON_ERROR),
        );
        self::assertSame(0, $this->fixture->dnsQueries);
    }

    /** @return iterable<string,array{string,?string,int,string,?string,?string}> */
    public static function policyCases(): iterable
    {
        yield 'public literal allowed' => ['https://8.8.8.8/?synthetic-private-query', null, 0, 'allow', null, null];
        yield 'private literal denied' => ['https://127.0.0.1/', null, 2, 'deny', 'address_forbidden', null];
        yield 'mapped public without DNS' => ['https://api.example/', null, 0, 'allow', null, null];
        yield 'unmapped name no DNS' => ['https://never-resolve.invalid/', null, 4, 'deny', 'resolution_unverified', null];
        yield 'configured endpoint allows private address' => ['https://erp.test/', 'erp', 0, 'allow', null, 'erp'];
        yield 'configured endpoint origin mismatch' => ['https://api.example/', 'erp', 2, 'deny', 'endpoint_mismatch', 'erp'];
        yield 'unknown endpoint cannot grant' => ['https://erp.test/', 'missing', 2, 'deny', 'grant_invalid', null];
    }

    #[DataProvider('bootFailures')]
    public function testBootFailureKeepsConfigAndLegacyResultsDiagnosticOnly(string $reason, int $exit): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $this->fixture->boot->recordAndBlock($reason);
        $result = $this->fixture->diagnostics->configCheck();
        self::assertSame($exit, $result->exitCode);
        self::assertSame(
            ['decision' => 'deny', 'reasonCode' => $reason, 'httpSent' => false, 'diagnosticOnly' => true],
            $result->payload,
        );
        $legacy = $this->fixture->diagnostics->legacyReport();
        self::assertSame(3, $legacy->exitCode);
        self::assertSame($reason, $legacy->payload['reasonCode']);
        self::assertFalse($legacy->payload['httpSent']);
        self::assertFalse($legacy->payload['policyChanged']);
        self::assertSame(0, $legacy->payload['automaticGrantsCreated']);
    }

    /** @return iterable<string,array{string,int}> */
    public static function bootFailures(): iterable
    {
        yield 'configuration' => ['configuration_invalid', 3];
        yield 'unsupported transport' => ['transport_unsupported', 3];
        yield 'unsupported proxy' => ['proxy_unsupported', 3];
        yield 'unverified DNS' => ['resolution_unverified', 4];
        yield 'target denied' => ['address_forbidden', 2];
    }

    public function testDefaultPolicyCheckMayResolveButTheInjectedIoBoundaryRemainsOffline(): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $result        = $this->fixture->diagnostics->policyCheck('https://never-resolve.invalid/');
        self::assertSame(1, $this->fixture->dnsQueries);
        self::assertSame(4, $result->exitCode);
        self::assertSame('unverifiable', $result->payload['decision']);
        self::assertSame('resolution_unverified', $result->payload['reasonCode']);
        self::assertArrayHasKey('profileId', $result->payload);
        self::assertNull($result->payload['profileId']);
        self::assertFalse($result->payload['httpSent']);
    }

    #[DataProvider('decisionFailures')]
    public function testEngineDecisionReasonsRetainOperationalExitMapping(
        string $reason,
        int $exit,
        string $decision,
    ): void {
        $this->fixture = new AdapterRuntimeFixture();
        $resolver      = new class ($reason) implements ResolverInterface {
            public function __construct(private readonly string $reason) {}

            public function resolve(string $canonicalHost): Resolution
            {
                throw new PolicyException($this->reason);
            }
        };
        $engine = $this->fixture->library->engine(
            $resolver,
            $this->fixture->policy,
            $this->fixture->clock,
            new NullDecisionReporter(),
        );
        $diagnostics = new DiagnosticsService(
            $this->fixture->loader,
            $this->fixture->boot,
            new LegacyConfigurationInventory(),
            fn () => $this->fixture->middleware,
            static fn () => $engine,
            fn () => $this->fixture->policy,
            $this->fixture->clock,
        );
        $result = $diagnostics->policyCheck('https://api.example/', null, true);
        self::assertSame($exit, $result->exitCode);
        self::assertSame($reason, $result->payload['reasonCode']);
        self::assertSame($decision, $result->payload['decision']);
        self::assertNull($result->payload['profileId']);
        self::assertFalse($result->payload['httpSent']);
        self::assertTrue($result->payload['diagnosticOnly']);
    }

    /** @return iterable<string,array{string,int,string}> */
    public static function decisionFailures(): iterable
    {
        yield 'configuration' => ['configuration_invalid', 3, 'deny'];
        yield 'transport' => ['transport_unsupported', 3, 'unverifiable'];
        yield 'proxy' => ['proxy_unsupported', 3, 'deny'];
        yield 'resolution' => ['resolution_unverified', 4, 'unverifiable'];
    }

    #[DataProvider('legacyShapes')]
    public function testLegacyReportInventoriesCountsAndMissingAuthorityWithoutChanges(
        mixed $allowed,
        int $flat,
        int $contexts,
        int $hosts,
        int $invalid,
    ): void {
        $this->fixture = new AdapterRuntimeFixture([], ['allowed_hosts' => $allowed]);
        $before        = $GLOBALS['TYPO3_CONF_VARS'];
        $result        = $this->fixture->diagnostics->legacyReport();
        self::assertSame(0, $result->exitCode);
        self::assertSame(
            [
                'automaticGrantsCreated' => 0,
                'sources'                => [
                    ['location' => 'HTTP.allowed_hosts', 'semantics' => 'flat Vault legacy entries', 'entries' => $flat],
                    [
                        'location'  => 'HTTP.allowed_hosts[context]',
                        'semantics' => 'Core context lists',
                        'contexts'  => $contexts,
                        'entries'   => $hosts,
                    ],
                ],
                'invalidEntries'         => $invalid,
                'requiredEndpointFields' => ['origin', 'allowedCidrs', 'methods', 'purpose', 'owner'],
                'missingLegacyFields'    => ['scheme', 'port', 'addressRanges', 'methods', 'purpose', 'owner'],
                'policyChanged'          => false,
                'httpSent'               => false,
                'reasonCode'             => null,
            ],
            $result->payload,
        );
        self::assertSame($before, $GLOBALS['TYPO3_CONF_VARS']);
    }

    /** @return iterable<string,array{mixed,int,int,int,int}> */
    public static function legacyShapes(): iterable
    {
        yield 'mixed flat and contextual inventory' => [
            [
                'flat-one',
                'flat-two',
                'good'  => ['nested-one', 'nested-two'],
                'mixed' => ['nested-three', false],
                null,
                7,
            ],
            2,
            2,
            3,
            3,
        ];
        yield 'null is absent' => [null, 0, 0, 0, 0];
        yield 'invalid scalar' => ['not-a-list', 0, 0, 0, 1];
        yield 'empty context' => [['context' => []], 0, 1, 0, 0];
    }

    public function testProcessProxyPresenceIsReportedWithItsValueRedacted(): void
    {
        $this->fixture = new AdapterRuntimeFixture();
        $name          = 'HTTPS_PROXY';
        try {
            putenv($name . '=http://private-process-value.invalid');
            $result = $this->fixture->diagnostics->doctor();
            self::assertSame(3, $result->exitCode);
            self::assertSame('proxy_unsupported', $result->payload['reasonCode']);
            self::assertFalse($result->payload['protected']);
            self::assertSame([$name], $result->payload['proxy']['processVariablesPresent']);
            self::assertStringNotContainsString(
                'private-process-value',
                json_encode($result->payload, JSON_THROW_ON_ERROR),
            );
        } finally {
            putenv($name);
        }
    }

    public function testDiagnosticBlockingPreservesOtherGlobalAndHttpConfiguration(): void
    {
        $this->fixture = new AdapterRuntimeFixture([], ['custom-http-option' => ['keep' => true]]);
        $before        = $GLOBALS['TYPO3_CONF_VARS'];
        $this->fixture->boot->recordAndBlock('configuration_invalid');
        self::assertSame('configuration_invalid', $this->fixture->boot->failureReason);
        self::assertSame($before['EXTCONF'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']);
        self::assertSame($before['SYS'], $GLOBALS['TYPO3_CONF_VARS']['SYS']);
        self::assertSame($before['LOG'], $GLOBALS['TYPO3_CONF_VARS']['LOG']);
        $afterHttp = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        unset($before['HTTP']['handler'], $afterHttp['handler']);
        self::assertSame($before['HTTP'], $afterHttp);
    }
}
