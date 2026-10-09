<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Http;

use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\NrHttpGuard\Configuration\ConfigurationLoader;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

/** Preserve the Core send signature and validate strings before PSR-7 parsing. */
trait RawRequestGuardTrait
{
    public function __construct(
        GuzzleClientFactory $guzzleFactory,
        private readonly ConfigurationLoader $configuration,
        private readonly RawRequestFactoryRegistration $registration,
        private readonly \Netresearch\HttpGuard\PolicyEngine $policyEngine,
        private readonly \Netresearch\HttpGuard\PolicyRegistry $policyRegistry,
        private readonly \TYPO3\CMS\Core\Http\RequestFactory $originalFactory
    )
    {
        if ($originalFactory::class !== \TYPO3\CMS\Core\Http\RequestFactory::class) {
            throw new \Netresearch\HttpGuard\PolicyException('configuration_invalid');
        }
        parent::__construct($guzzleFactory);
    }
    /** @param array<array-key, mixed> $options */
    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null
    ): ResponseInterface
    {
        $this->registration->assertValid();
        $mode = $this->configuration->load()->mode;
        if ($mode !== 'disabled') {
            try {
                (new TargetNormalizer())->assertRawUri($uri);
            } catch (\Netresearch\HttpGuard\PolicyException $error) {
                $this->policyEngine->reportDiagnostic(
                    $this->policyRegistry->newContext(),
                    $mode === 'observe' ? 'would_deny' : 'deny',
                    $error->reasonCode()
                );
                if ($mode === 'enforce') {
                    throw $error;
                }
            }
        }
        return $this->originalFactory->request($uri, $method, $options, $context);
    }
}
