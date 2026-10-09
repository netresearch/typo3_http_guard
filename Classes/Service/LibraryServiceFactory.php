<?php

declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Service;

use Netresearch\HttpGuard\ClockInterface;
use Netresearch\HttpGuard\DecisionReporter;
use Netresearch\HttpGuard\DecisionReporterInterface;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\StaticThenDnsResolver;
use Netresearch\HttpGuard\WireDnsQuery;
use Netresearch\HttpGuard\TargetNormalizer;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use Netresearch\NrHttpGuard\Http\CoreStackProvider;
use TYPO3\CMS\Core\Log\LogManager;
final readonly class LibraryServiceFactory
{
    public function __construct(private LogManager $logManager)
    {
    }
    public function reporter(
        GuardConfig $config,
        ClockInterface $clock
    ): DecisionReporterInterface
    {
        $logger = $this->logManager->getLogger('Netresearch.HttpGuard');
        $env = $config->data['logging']['hostHmacKeyEnv'];
        $key = $env === null ? false : getenv($env, true);
        return new DecisionReporter(
            $config,
            $clock,
            static function (array $event) use ($logger): void {
                $logger->log(
                    ($event['decision'] ?? null) === 'allow' ? 'info' : 'warning',
                    'Outbound HTTP policy decision',
                    $event
                );
            },
            is_string($key) && $key !== '' ? $key : null
        );
    }
    public function resolver(
        GuardConfig $config,
        ClockInterface $clock
    ): ResolverInterface
    {
        return new StaticThenDnsResolver($config, new WireDnsQuery(), $clock);
    }
    public function registry(
        GuardConfig $config,
        ClockInterface $clock
    ): PolicyRegistry
    {
        return new PolicyRegistry($config, $clock);
    }
    public function engine(
        ResolverInterface $resolver,
        PolicyRegistry $registry,
        ClockInterface $clock,
        DecisionReporterInterface $reporter
    ): PolicyEngine
    {
        return new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            $reporter
        );
    }
    public function globalFactory(
        PolicyEngine $engine,
        GuardConfig $config,
        PolicyRegistry $registry
    ): GuardedClientFactory
    {
        return new GuardedClientFactory($engine, $config, $registry);
    }
    public function contextFactory(
        PolicyEngine $engine,
        GuardConfig $config,
        PolicyRegistry $registry,
        CoreStackProvider $provider
    ): GuardedClientFactory
    {
        return new GuardedClientFactory($engine, $config, $registry, $provider);
    }
}
