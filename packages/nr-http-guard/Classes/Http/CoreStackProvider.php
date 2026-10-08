<?php

declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Http;

use GuzzleHttp\HandlerStack;
use Netresearch\HttpGuard\Client\ClientStackConfiguration;
use Netresearch\HttpGuard\Client\ClientStackProviderInterface;
use Netresearch\HttpGuard\PolicyException;
use Netresearch\HttpGuard\Transport\BoundaryMiddleware;
use Netresearch\HttpGuard\Transport\TerminalGuardMiddleware;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
final readonly class CoreStackProvider implements ClientStackProviderInterface
{
    public function __construct(
        private GuzzleClientFactory $coreFactory,
        private MiddlewareRegistry $registry,
        private string $context = 'nr_http_guard'
    )
    {
    }
    public function create(
        BoundaryMiddleware $boundary,
        TerminalGuardMiddleware $terminal
    ): ClientStackConfiguration
    {
        $registered = $this->registry->binding();
        $client = $this->coreFactory->getClient($this->context);
        if (!$client instanceof \GuzzleHttp\Client) {
            throw new PolicyException('configuration_invalid');
        }
        $defaults = $client->getConfig();
        $originalStack = $defaults['handler'] ?? null;
        if (!$originalStack instanceof HandlerStack) {
            throw new PolicyException('configuration_invalid');
        }
        $stack = clone $originalStack;
        $stack->before(
            MiddlewareRegistry::BOUNDARY,
            $boundary,
            MiddlewareRegistry::BOUNDARY
        );
        $stack->remove($registered->boundary);
        $stack->before(
            MiddlewareRegistry::TERMINAL,
            $terminal,
            MiddlewareRegistry::TERMINAL
        );
        $stack->remove($registered->terminal);
        $defaults['handler'] = $stack;
        $assertion = function (): void {
            $this->registry->assertValid();
        };
        $boundary->setRegistryAssertion($assertion);
        $terminal->setRegistryAssertion($assertion);
        return new ClientStackConfiguration($stack, $defaults, $assertion);
    }
}
