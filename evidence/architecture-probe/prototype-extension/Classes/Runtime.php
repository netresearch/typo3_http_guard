<?php

declare (strict_types=1);

namespace HttpGuardProbe;

use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;

final class State
{
    public static array $trace = [];
    public static array $leases = [];
    public static array $weakHandlers = [];
    public static array $plans = [];
    public static array $observedIps = [];
    public static int $attempts = 0;
    public static int $released = 0;
    public static int $registered = 0;
    public static int $peakLeases = 0;
    public static bool $rewrite = false;
    public static bool $lateRedirect = false;
    public static ?Boundary $boundary = null;
    public static ?Terminal $terminal = null;

    public static function origin(RequestInterface $request): string
    {
        $uri = $request->getUri();
        return strtolower($uri->getScheme()) . '://' . strtolower($uri->getHost()) . ':' . ($uri->getPort() ?? ($uri->getScheme() === 'https' ? 443 : 80));
    }

    public static function assertRegistry(): void
    {
        $registry = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] ?? null;
        if (!is_array($registry) || $registry === []) {
            throw new \RuntimeException(
                'configuration_invalid: registry must be middleware array'
            );
        }
        $values = array_values($registry);
        if ($values[0] !== self::$boundary || $values[array_key_last($values)] !== self::$terminal) {
            throw new \RuntimeException(
                'configuration_invalid: boundary or terminal position'
            );
        }
        $boundaries = count(array_filter($values, fn($value) => $value instanceof Boundary));
        $terminals = count(array_filter($values, fn($value) => $value instanceof Terminal));
        if ($boundaries !== 1 || $terminals !== 1) {
            throw new \RuntimeException('configuration_invalid: duplicated guard');
        }
    }
}

final class BootstrapListener
{
    public function __invoke(BootCompletedEvent $event): void
    {
        $registry = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] ?? [];
        if (!is_array($registry)) {
            throw new \RuntimeException(
                'configuration_invalid: HandlerStack registry'
            );
        }
        foreach ($registry as $middleware) {
            if ($middleware instanceof Boundary || $middleware instanceof Terminal) {
                throw new \RuntimeException(
                    'configuration_invalid: duplicated registration'
                );
            }
        }
        if (State::$registered !== 0) {
            throw new \RuntimeException(
                'configuration_invalid: repeated bootstrap registration'
            );
        }
        State::$boundary = new Boundary();
        State::$terminal = new Terminal();
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = ['nr/http-guard-boundary' => State::$boundary] + $registry + ['nr/http-guard-terminal' => State::$terminal];
        State::assertRegistry();
        State::$registered++;
    }
}

final class Envelope
{
    public function __construct(public readonly string $origin)
    {
    }
}

final class Boundary
{
    public function __invoke(callable $next): callable
    {
        return function (
            RequestInterface $request,
            array $options
        ) use ($next): PromiseInterface {
            State::assertRegistry();
            State::$trace[] = 'boundary.request';
            $envelope = new Envelope(State::origin($request));
            $options['_probe_envelope'] = $envelope;
            return $next($request, $options)->then(
                function (
                    ResponseInterface $response
                ) use ($request, $options, $envelope): ResponseInterface {
                    State::$trace[] = 'boundary.response';
                    if (!empty($options['allow_redirects']) && $response->hasHeader('Location') && in_array(
                        $response->getStatusCode(),
                        [301, 302, 303, 307, 308],
                        true
                    )) {
                        $nextUri = \GuzzleHttp\Psr7\UriResolver::resolve(
                            $request->getUri(),
                            new \GuzzleHttp\Psr7\Uri(
                                $response->getHeaderLine('Location')
                            )
                        );
                        if (State::origin($request->withUri($nextUri)) !== $envelope->origin) {
                            throw new \RuntimeException(
                                'redirect_forbidden: final response origin'
                            );
                        }
                    }
                    return $response;
                }
            );
        };
    }
}

final class ProjectMiddleware
{
    public function __construct(private readonly string $name)
    {
    }

    public function __invoke(callable $next): callable
    {
        return function (
            RequestInterface $request,
            array $options
        ) use ($next): PromiseInterface {
            State::$trace[] = $this->name . '.request';
            if (State::$rewrite && $this->name === 'B') {
                $request = $request->withUri(
                    $request->getUri()->withHost('rewritten.test')
                );
            }
            return $next($request, $options)->then(
                function (ResponseInterface $response): ResponseInterface {
                    State::$trace[] = $this->name . '.response';
                    if (State::$lateRedirect && $this->name === 'B') {
                        return $response
                            ->withStatus(302)
                            ->withHeader('Location', 'http://forbidden.test:8080/b');
                    }
                    return $response;
                }
            );
        };
    }
}

final class Terminal
{
    public function __invoke(callable $unusedStandardLeaf): callable
    {
        return function (RequestInterface $request, array $options): PromiseInterface {
            State::$trace[] = 'terminal.request';
            $envelope = $options['_probe_envelope'] ?? null;
            if (!$envelope instanceof Envelope || State::origin($request) !== $envelope->origin) {
                throw new \RuntimeException('authority_mismatch: middleware origin');
            }
            if (($options['stream'] ?? false) || !empty($options['curl']) || !empty($options['delay'])) {
                throw new \RuntimeException(
                    'option_forbidden: uncontrolled transport'
                );
            }
            $known = [
                '_probe_envelope',
                'handler',
                'allow_redirects',
                'cookies',
                'http_errors',
                'decode_content',
                'verify',
                'idn_conversion',
                'timeout',
                'connect_timeout',
                'synchronous',
                'on_headers',
                'on_stats',
                'progress',
                'sink',
                'cert',
                'ssl_key',
                'force_ip_resolve',
                'expect',
                'stream',
                'delay',
                'proxy',
                'auth',
                'read_timeout',
                'request_factory',
                'response_factory',
                'stream_factory',
                'uri_factory',
                'multiplex',
                '__redirect_count',
                '__guzzle_digest_retries',
                'protocols',
                'retries',
            ];
            foreach (array_keys($options) as $key) {
                if (!in_array($key, $known, true)) {
                    throw new \RuntimeException('option_forbidden: unknown option');
                }
            }
            if (!empty($options['proxy'])) {
                throw new \RuntimeException('proxy_unsupported');
            }
            $address = str_contains($request->getUri()->getPath(), 'b') ? '203.0.114.101' : '203.0.114.100';
            $pin = $request->getUri()->getHost() . ':' . ($request->getUri()->getPort() ?? 80) . ':' . $address;
            $leafOptions = array_intersect_key(
                $options,
                array_flip(
                    [
                        'timeout',
                        'connect_timeout',
                        'decode_content',
                        'verify',
                        'on_headers',
                        'on_stats',
                        'progress',
                        'sink',
                        'cert',
                        'ssl_key',
                        'force_ip_resolve',
                        'expect',
                        'request_factory',
                        'response_factory',
                        'stream_factory',
                        'uri_factory',
                    ]
                )
            );
            $leafOptions['proxy'] = '';
            $leafOptions['curl'] = [
                CURLOPT_RESOLVE => [$pin],
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_FORBID_REUSE => true,
            ];
            return (new Lease())
                ->start($request, $leafOptions, $address, $pin)
                ->then(
                    function (ResponseInterface $response): ResponseInterface {
                        State::$trace[] = 'terminal.response';
                        return $response;
                    }
                );
        };
    }
}

final class Lease
{
    private ?CurlMultiHandler $handler = null;
    private ?PromiseInterface $inner = null;
    private ?PromiseInterface $outer = null;
    private bool $done = false;
    private int $id;

    public function start(
        RequestInterface $request,
        array $options,
        string $address,
        string $pin
    ): PromiseInterface
    {
        $this->id = ++State::$attempts;
        $this->handler = new CurlMultiHandler(
            [
                'transport_sharing' => 'none',
                'select_timeout' => 0.01,
                'handle_factory' => new \GuzzleHttp\Handler\CurlFactory(0),
            ]
        );
        State::$weakHandlers[] = \WeakReference::create($this->handler);
        State::$plans[$this->id] = [
            'address' => $address,
            'pin' => $pin,
            'handlerId' => spl_object_id($this->handler),
            'transportSharing' => 'none',
            'curlKeys' => array_keys($options['curl']),
        ];
        State::$leases[$this->id] = $this;
        State::$peakLeases = max(State::$peakLeases, count(State::$leases));
        $userHeaders = $options['on_headers'] ?? null;
        $options['on_headers'] = static function (
            ResponseInterface $response,
            ...$extra
        ) use ($userHeaders): void {
            State::$trace[] = 'wire.on_headers';
            if ($userHeaders) {
                $userHeaders($response, ...$extra);
            }
        };
        $userStats = $options['on_stats'] ?? null;
        $options['on_stats'] = function (
            \GuzzleHttp\TransferStats $stats
        ) use ($userStats, $address): void {
            State::$trace[] = 'wire.on_stats';
            $primary = $stats->getHandlerStat('primary_ip');
            State::$observedIps[] = $primary;
            if ($stats->hasResponse() && $primary !== $address) {
                throw new \RuntimeException('pin_mismatch_after_transfer');
            }
            if ($userStats) {
                $userStats($stats);
            }
        };
        try {
            $this->inner = ($this->handler)($request, $options);
            $this->outer = new Promise(
                function (): void {
                    $outer = $this->outer;
                    try {
                        $result = $this->inner->wait();
                        $outer->resolve($result);
                    } catch (\Throwable $e) {
                        $outer->reject($e);
                    } finally {
                        $this->release();
                    }
                },
                function (): void {
                    $this->inner?->cancel();
                    $this->release();
                }
            );
            $this->inner->then(
                function (ResponseInterface $response): void {
                    $this->outer?->resolve($response);
                    $this->release();
                },
                function ($reason): void {
                    $this->outer?->reject($reason);
                    $this->release();
                }
            );
            return $this->outer;
        } catch (\Throwable $e) {
            $this->release();
            throw $e;
        }
    }

    public function tick(): void
    {
        $this->handler?->tick();
    }

    private function release(): void
    {
        if ($this->done) {
            return;
        }
        $this->done = true;
        if ($this->handler) {
            if (method_exists($this->handler, 'close')) {
                $this->handler->close();
                State::$plans[$this->id]['closeMethod'] = 'close';
            } else {
                $this->handler->__destruct();
                State::$plans[$this->id]['closeMethod'] = '__destruct';
            }
        }
        $this->handler = null;
        $this->inner = null;
        $this->outer = null;
        unset(State::$leases[$this->id]);
        State::$released++;
    }
}
