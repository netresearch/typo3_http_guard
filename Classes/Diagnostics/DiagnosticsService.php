<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

namespace Netresearch\NrHttpGuard\Diagnostics;

use Closure;
use Composer\InstalledVersions;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\NrHttpGuard\Configuration\ConfigurationLoader;
use Throwable;

final readonly class DiagnosticsService
{
    /**
     * @param Closure(): \Netresearch\NrHttpGuard\Http\MiddlewareRegistry $registry
     * @param Closure(): \Netresearch\HttpGuard\PolicyEngine              $engine
     * @param Closure(): \Netresearch\HttpGuard\PolicyRegistry            $policyRegistry
     */
    public function __construct(
        private ConfigurationLoader $loader,
        private DiagnosticBootState $boot,
        private LegacyConfigurationInventory $legacy,
        private Closure $registry,
        private Closure $engine,
        private Closure $policyRegistry,
        private \Netresearch\HttpGuard\ClockInterface $clock,
    ) {}

    public function doctor(): DiagnosticResult
    {
        $curlInfo    = extension_loaded('curl') ? curl_version() : false;
        $curlVersion = is_array($curlInfo) && is_string($curlInfo['version'] ?? null) ? $curlInfo['version'] : null;
        $versions    = ['php' => PHP_VERSION, 'curl' => $curlVersion];
        foreach (['guzzlehttp/guzzle', 'guzzlehttp/promises', 'guzzlehttp/psr7'] as $name) {
            $versions[$name] = InstalledVersions::isInstalled($name) ? InstalledVersions::getPrettyVersion($name) : null;
        }
        $versions['typo3/cms-core'] = (new \TYPO3\CMS\Core\Information\Typo3Version())->getVersion();
        $variables                  = \Netresearch\HttpGuard\Transport\OptionSanitizer::processProxyNames();
        $base                       = [
            'eventVersion' => 1,
            'versions'     => $versions,
            'proxy'        => [
                'processVariablesPresent' => $variables,
                'serverHttpProxyPresent'  => isset($_SERVER['HTTP_PROXY']),
                'valuesRedacted'          => true,
            ],
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
        ];
        try {
            $config = $this->configuration();
            ($this->registry)()->assertValid();
            $protected = $config->mode === 'enforce';
            $httpProxy = $this->httpConfiguration()['proxy'] ?? null;
            $hasProxy  = $variables !== [] || !in_array($httpProxy, [null, '', false, []], true);
            $supported = true;
            try {
                \Netresearch\HttpGuard\Transport\RuntimeSupport::assertSupported();
                \Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility::assertSupported();
            } catch (PolicyException) {
                $supported = false;
            }
            $code = !$supported || $hasProxy ? 3 : ($protected ? 0 : 2);

            return new DiagnosticResult(
                $base + [
                    'mode'           => $config->mode,
                    'policyRevision' => $config->revision,
                    'registryValid'  => true,
                    'warnings'       => $this->warnings($config),
                    'protected'      => $protected && $supported && !$hasProxy,
                    'reasonCode'     => !$supported ? 'transport_unsupported' : ($hasProxy ? 'proxy_unsupported' : null),
                ],
                $code,
            );
        } catch (PolicyException $exception) {
            return new DiagnosticResult(
                $base + ['registryValid' => false, 'protected' => false, 'reasonCode' => $exception->reasonCode()],
                3,
            );
        }
    }

    public function configCheck(): DiagnosticResult
    {
        try {
            $config = $this->configuration();

            return new DiagnosticResult(
                [
                    'schemaVersion'  => 1,
                    'mode'           => $config->mode,
                    'policyRevision' => $config->revision,
                    'endpointCount'  => count($config->data['endpoints']),
                    'warnings'       => $this->warnings($config),
                    'httpSent'       => false,
                ],
                0,
            );
        } catch (PolicyException $exception) {
            return $this->failure($exception);
        }
    }

    public function legacyReport(): DiagnosticResult
    {
        try {
            $report = $this->legacy->inspect($this->httpConfiguration());

            return new DiagnosticResult(
                $report + ['httpSent' => false, 'reasonCode' => $this->boot->failureReason],
                $this->boot->failureReason === null ? 0 : 3,
            );
        } catch (PolicyException $exception) {
            return $this->failure($exception);
        }
    }

    public function policyCheck(string $url, ?string $endpointId = null, bool $noDns = false): DiagnosticResult
    {
        try {
            $config     = $this->configuration();
            $normalizer = new TargetNormalizer();
            $normalizer->assertRawUri($url);
            try {
                $request = new Request('GET', $url);
            } catch (Throwable) {
                throw new PolicyException('invalid_target');
            }
            $target = $normalizer->normalize($request);
            if ($noDns && $target->literalIp === null && !array_key_exists($target->host, $config->data['resolver']['staticHosts'])) {
                throw new PolicyException('resolution_unverified');
            }
            $context  = ($this->policyRegistry)()->newContext($endpointId);
            $decision = ($this->engine)()->evaluate($request, $context);
            $code     = match ($decision->reasonCode) {
                'configuration_invalid', 'transport_unsupported', 'proxy_unsupported' => 3,
                'resolution_unverified'                                               => 4,
                default                                                               => $decision->decision === 'allow' ? 0 : 2,
            };

            return new DiagnosticResult(
                [
                    'mode'           => $decision->mode,
                    'decision'       => $decision->decision,
                    'reasonCode'     => $decision->reasonCode,
                    'profileId'      => $decision->profileId,
                    'policyRevision' => $decision->policyRevision,
                    'httpSent'       => false,
                    'diagnosticOnly' => true,
                ],
                $code,
            );
        } catch (PolicyException $exception) {
            return $this->failure($exception);
        }
    }

    private function configuration(): GuardConfig
    {
        if ($this->boot->failureReason !== null) {
            throw new PolicyException($this->boot->failureReason);
        }

        return $this->loader->load();
    }

    private function failure(PolicyException $exception): DiagnosticResult
    {
        $code = match ($exception->reasonCode()) {
            'configuration_invalid', 'transport_unsupported', 'proxy_unsupported' => 3,
            'resolution_unverified'                                               => 4,
            default                                                               => 2,
        };

        return new DiagnosticResult(
            [
                'decision'       => 'deny',
                'reasonCode'     => $exception->reasonCode(),
                'httpSent'       => false,
                'diagnosticOnly' => true,
            ],
            $code,
        );
    }

    /** @return array{overdueEndpointReviews: list<string>, tlsVerificationPolicyNotRequired: bool} */
    private function warnings(GuardConfig $config): array
    {
        $overdue = [];
        $today   = $this->clock->now()->format('Y-m-d');
        foreach ($config->data['endpoints'] as $id => $endpoint) {
            if ($endpoint['reviewAfter'] !== null && $endpoint['reviewAfter'] < $today) {
                $overdue[] = $id;
            }
        }

        return [
            'overdueEndpointReviews'           => $overdue,
            'tlsVerificationPolicyNotRequired' => $config->data['tls']['requireVerification'] === false,
        ];
    }

    /** @return array<string, mixed> */
    private function httpConfiguration(): array
    {
        $configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        if (!is_array($configuration)) {
            throw new PolicyException('configuration_invalid');
        }
        $http = $configuration['HTTP'] ?? [];
        if (!is_array($http)) {
            throw new PolicyException('configuration_invalid');
        }
        $validated = [];
        foreach ($http as $key => $value) {
            if (!is_string($key)) {
                throw new PolicyException('configuration_invalid');
            }
            $validated[$key] = $value;
        }

        return $validated;
    }
}
