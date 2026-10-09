<?php

/**
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
declare (strict_types=1);
namespace Netresearch\HttpGuard;

final class DecisionReporter implements DecisionReporterInterface
{
    /**
     * @var array<string,array{labels:array{mode:string,decision:string,reasonCode:?string,profileId:?string},count:int}>
     */
    private array $counts = [];
    private float $windowStart = -1;
    private int $windowCount = 0;
    private int $loggerFailures = 0;
    /** @param (\Closure(array<string,mixed>): void)|null $logger */
    public function __construct(
        private readonly GuardConfig $config,
        private readonly ClockInterface $clock,
        private readonly ?\Closure $logger = null,
        private readonly ?string $hostHmacKey = null
    )
    {
    }
    public function report(DecisionEvent $event): void
    {
        $mode = in_array($event->mode, ['enforce', 'observe', 'disabled'], true) ? $event->mode : 'enforce';
        $decision = in_array(
            $event->decision,
            ['allow', 'deny', 'would_deny', 'unverifiable'],
            true
        ) ? $event->decision : 'unverifiable';
        $reason = $event->reasonCode === null ? null : (in_array($event->reasonCode, PolicyException::REASONS, true) ? $event->reasonCode : 'configuration_invalid');
        $profile = $event->profileId !== null && isset($this->config->data['endpoints'][$event->profileId]) ? $event->profileId : null;
        $labels = [
            'mode' => $mode,
            'decision' => $decision,
            'reasonCode' => $reason,
            'profileId' => $profile,
        ];
        $key = json_encode($labels, JSON_THROW_ON_ERROR);
        if (!isset($this->counts[$key])) {
            $this->counts[$key] = ['labels' => $labels, 'count' => 0];
        }
        ++$this->counts[$key]['count'];
        $logging = $this->config->data['logging'];
        if ($decision === 'allow') {
            $rate = $logging['allowedSampleRate'];
            if ($rate <= 0 || $rate < 1 && random_int(0, 1000000) / 1000000 > $rate) {
                return;
            }
        } else {
            $now = $this->clock->monotonic();
            if ($this->windowStart < 0 || $now - $this->windowStart >= 60) {
                $this->windowStart = $now;
                $this->windowCount = 0;
            }
            if ($this->windowCount >= $logging['denyRateLimitPerMinute']) {
                return;
            }
            ++$this->windowCount;
        }
        if ($this->logger === null) {
            return;
        }
        $row = [
            'version' => 1,
            'time' => $this->clock->now()->format(\DateTimeInterface::RFC3339_EXTENDED),
            'mode' => $mode,
            'decision' => $decision,
            'reasonCode' => $reason,
            'profileId' => $profile,
            'policyRevision' => $this->config->revision,
            'addressClass' => self::safe($event->addressClass),
            'scheme' => in_array($event->scheme, ['http', 'https'], true) ? $event->scheme : null,
            'port' => $event->port !== null && $event->port >= 1 && $event->port <= 65535 ? $event->port : null,
            'resolverSource' => self::safe($event->resolverSource),
            'correlationId' => preg_match('/^[0-9a-f]{24,64}$/D', $event->correlationId) === 1 ? $event->correlationId : null,
        ];
        if ($event->host !== null) {
            try {
                $host = TargetNormalizer::host($event->host);
            } catch (PolicyException) {
                $host = null;
            }
            if ($host !== null) {
                if ($logging['hostMode'] === 'plain') {
                    $row['host'] = $host;
                } elseif ($logging['hostHmacKeyEnv'] !== null && $this->hostHmacKey !== null && $this->hostHmacKey !== '') {
                    $row['host'] = hash_hmac('sha256', $host, $this->hostHmacKey);
                }
            }
        }
        try {
            ($this->logger)($row);
        } catch (\Throwable) {
            ++$this->loggerFailures;
        }
    }
    /**
     * @return list<array{labels:array{mode:string,decision:string,reasonCode:?string,profileId:?string},count:int}>
     */
    public function metrics(): array
    {
        return array_values($this->counts);
    }
    public function loggerFailureCount(): int
    {
        return $this->loggerFailures;
    }
    private static function safe(?string $value): ?string
    {
        return $value !== null && preg_match('/^[a-zA-Z0-9_+-]{1,64}$/D', $value) === 1 ? $value : null;
    }
}
