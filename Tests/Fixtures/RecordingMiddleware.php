<?php

declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Tests\Fixtures;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
final class ProbeState
{
    public static array $trace = [];
    public static bool $rewrite = false;
    public static bool $lateRedirect = false;
}
final readonly class RecordingMiddleware
{
    public function __construct(private string $name)
    {
    }
    public function __invoke(callable $next): callable
    {
        return function (
            RequestInterface $request,
            array $options
        ) use ($next): PromiseInterface {
            ProbeState::$trace[] = $this->name . '.request';
            if (ProbeState::$rewrite && $this->name === 'B') {
                $request = $request->withUri($request->getUri()->withHost('erp.test'));
            }
            return $next($request, $options)->then(
                function (ResponseInterface $response): ResponseInterface {
                    ProbeState::$trace[] = $this->name . '.response';
                    return ProbeState::$lateRedirect && $this->name === 'B' ? $response
                        ->withStatus(302)
                        ->withHeader('Location', 'http://erp.test:8080/leak') : $response;
                }
            );
        };
    }
}
