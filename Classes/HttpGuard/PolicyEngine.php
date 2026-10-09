<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard;

final class PolicyEngine implements OutboundPolicyEvaluatorInterface
{
    /**
     * @var \WeakMap<ConnectionPlan,RequestPolicyContext>
     */
    private \WeakMap $plans;
    public function __construct(
        private readonly TargetNormalizer $normalizer,
        private readonly AddressClassifier $classifier,
        private readonly ResolverInterface $resolver,
        private readonly PolicyRegistry $registry,
        private readonly ClockInterface $clock,
        private readonly DecisionReporterInterface $reporter
    )
    {
        $this->plans = new \WeakMap();
    }
    public function plan(
        \Psr\Http\Message\RequestInterface $request,
        RequestPolicyContext $context
    ): ConnectionPlan
    {
        $target = null;
        $metadata = ['source' => 'none', 'class' => 'unknown'];
        try {
            $plan = $this->buildPlan($request, $context, $target, $metadata);
            $this->reportDiagnostic(
                $context,
                'allow',
                null,
                $target,
                $metadata['source'],
                $metadata['class']
            );
            return $plan;
        } catch (PolicyException $e) {
            $this->reportDiagnostic(
                $context,
                'deny',
                $e->reasonCode(),
                $target,
                $metadata['source'],
                $metadata['class']
            );
            throw $e;
        }
    }
    public function evaluate(
        \Psr\Http\Message\RequestInterface $request,
        RequestPolicyContext $context
    ): PolicyDecision
    {
        $target = null;
        $metadata = ['source' => 'none', 'class' => 'unknown'];
        try {
            $plan = $this->buildPlan($request, $context, $target, $metadata);
            $this->reportDiagnostic(
                $context,
                'allow',
                null,
                $target,
                $metadata['source'],
                $metadata['class']
            );
            return new PolicyDecision(
                $context->mode,
                'allow',
                null,
                $plan->profileId,
                $context->policyRevision
            );
        } catch (PolicyException $e) {
            $decision = $context->mode === 'observe' ? 'would_deny' : 'deny';
            if (in_array(
                $e->reasonCode(),
                ['resolution_unverified', 'transport_unsupported'],
                true
            )) {
                $decision = 'unverifiable';
            }
            $this->reportDiagnostic(
                $context,
                $decision,
                $e->reasonCode(),
                $target,
                $metadata['source'],
                $metadata['class']
            );
            return new PolicyDecision(
                $context->mode,
                $decision,
                $e->reasonCode(),
                $this->profileId($context),
                $context->policyRevision
            );
        }
    }
    public function assertCurrent(
        ConnectionPlan $plan,
        RequestPolicyContext $context
    ): void
    {
        if (!isset($this->plans[$plan]) || $this->plans[$plan] !== $context || $plan->policyRevision !== $context->policyRevision) {
            throw new PolicyException('grant_invalid');
        }
        $this->registry->validateContext($context);
    }
    public function hostAllowed(
        string $host,
        RequestPolicyContext $context
    ): bool
    {
        try {
            $profile = $this->registry->validateContext($context);
            $canonical = TargetNormalizer::host($host);
            if ($profile !== null) {
                $origin = new \GuzzleHttp\Psr7\Uri($profile->origin);
                if (TargetNormalizer::host($origin->getHost()) !== $canonical) {
                    return false;
                }
            }
            $addresses = str_contains($canonical, ':') || preg_match('/^[0-9.]+$/D', $canonical) === 1 ? [Cidr::address($canonical)] : $this->resolver->resolve($canonical)->addresses;
            $this->validateAddresses($addresses, $profile);
            $this->registry->validateContext($context);
            return true;
        } catch (PolicyException) {
            return false;
        }
    }
    public function reportDiagnostic(
        RequestPolicyContext $context,
        string $decision,
        ?string $reasonCode = null,
        ?Target $target = null,
        string $resolverSource = 'none',
        string $addressClass = 'unknown'
    ): void
    {
        $event = new DecisionEvent(
            1,
            $this->clock->now()->format(\DateTimeInterface::RFC3339_EXTENDED),
            $context->mode,
            $decision,
            $reasonCode,
            $this->profileId($context),
            $context->policyRevision,
            $addressClass,
            $target?->scheme,
            $target?->port,
            $resolverSource,
            bin2hex(random_bytes(12)),
            $target?->host
        );
        try {
            $this->reporter->report($event);
        } catch (\Throwable) {
        }
    }
    /** @param array{source:string,class:string} $metadata
     * @param-out Target $target
     */
    private function buildPlan(
        \Psr\Http\Message\RequestInterface $request,
        RequestPolicyContext $context,
        ?Target &$target = null,
        array &$metadata = ['source' => 'none', 'class' => 'unknown']
    ): ConnectionPlan
    {
        $profile = $this->registry->validateContext($context);
        $target = $this->normalizer->normalize($request);
        $method = $request->getMethod();
        if (strtoupper($method) === 'CONNECT') {
            throw new PolicyException('invalid_target');
        }
        if ($profile !== null && ($profile->origin !== $target->origin || !in_array($method, $profile->methods, true))) {
            throw new PolicyException('endpoint_mismatch');
        }
        $resolution = $target->literalIp !== null ? new Resolution([$target->literalIp], 'literal', null, 'literal') : $this->resolver->resolve($target->host);
        $metadata = ['source' => $resolution->source, 'class' => 'unknown'];
        $addresses = $this->validateAddresses($resolution->addresses, $profile, $metadata);
        $this->registry->validateContext($context);
        $plan = new ConnectionPlan(
            $target,
            $method,
            $addresses,
            $profile?->id,
            $context->policyRevision,
            $resolution->generation,
            $this->clock->now()
        );
        $this->plans[$plan] = $context;
        return $plan;
    }
    /** @param array<array-key,mixed> $addresses
     * @param array{source:string,class:string} $metadata
     * @return list<string>
     */
    private function validateAddresses(
        array $addresses,
        ?EndpointProfile $profile,
        array &$metadata = ['source' => 'none', 'class' => 'unknown']
    ): array
    {
        if (!array_is_list($addresses) || $addresses === []) {
            throw new PolicyException('resolution_unverified');
        }
        if (count($addresses) > $this->registry->configuration()->data['resolver']['maxAddresses']) {
            throw new PolicyException('resolution_limit');
        }
        [$result, $classes] = [[], []];
        foreach ($addresses as $address) {
            if (!is_string($address)) {
                throw new PolicyException('resolution_unverified');
            }
            try {
                [$classification, $classes] = $this->classifyForMetadata($address, $classes, $metadata);
            } catch (PolicyException) {
                throw new PolicyException('resolution_unverified');
            }
            foreach ($this->registry->configuration()->data['deniedCidrs'] as $deny) {
                if ($this->classifier->contains($deny, $classification->canonicalIp)) {
                    throw new PolicyException('address_forbidden');
                }
            }
            if ($classification->hardDenied) {
                throw new PolicyException('address_forbidden');
            }
            if ($profile === null) {
                if (!$classification->public) {
                    throw new PolicyException('address_forbidden');
                }
            } else {
                if (!$classification->public && !$classification->endpointExceptable) {
                    throw new PolicyException('address_forbidden');
                }
                if ($classification->addressClass === 'loopback' && !$profile->allowLoopback) {
                    throw new PolicyException('address_forbidden');
                }
                $matches = false;
                foreach ($profile->allowedCidrs as $cidr) {
                    if ($this->classifier->contains(
                        $cidr,
                        $classification->canonicalIp
                    )) {
                        $matches = true;
                        break;
                    }
                }
                if (!$matches) {
                    throw new PolicyException('endpoint_mismatch');
                }
            }
            $result[] = $classification->canonicalIp;
        }
        return array_values(array_unique($result));
    }
    private function profileId(RequestPolicyContext $context): ?string
    {
        try {
            return $this->registry->validateContext($context)?->id;
        } catch (PolicyException) {
            return null;
        }
    }
    /** @param array<string,true> $classes
     * @param array{source:string,class:string} $metadata
     * @return array{AddressClassification,array<string,true>}
     */
    private function classifyForMetadata(
        string $address,
        array $classes,
        array &$metadata
    ): array
    {
        $classification = $this->classifier->classify($address);
        $classes[$classification->addressClass] = true;
        $metadata['class'] = count($classes) === 1 ? $classification->addressClass : 'mixed';
        return [$classification, $classes];
    }
}
