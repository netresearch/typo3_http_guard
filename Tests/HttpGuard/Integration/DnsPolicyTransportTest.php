<?php

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Integration;

use Closure;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Request;
use LogicException;
use Netresearch\HttpGuard\{AddressClassifier, ClockInterface, DnsAnswer, DnsPacketCodec, DnsQueryInterface, GuardConfig, NullDecisionReporter, PolicyEngine, PolicyException, PolicyRegistry, StaticThenDnsResolver, TargetNormalizer};
use Netresearch\HttpGuard\Client\{GuardedClientBinding, GuardedClientFactory};
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class DnsTransportClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-08T00:00:00Z');
    }

    public function monotonic(): float
    {
        return 0;
    }
}
final class DnsTransportQuery implements DnsQueryInterface
{
    public int $calls = 0;

    public function __construct(private readonly Closure $answer) {}

    public function query(string $name, int $type): DnsAnswer
    {
        ++$this->calls;

        return ($this->answer)($name, $type);
    }
}
final class DnsPolicyTransportTest extends TestCase
{
    private array $proxyEnvironment = [];

    protected function setUp(): void
    {
        foreach (getenv(null, true) as $key => $value) {
            if (in_array(strtolower((string) $key), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                $this->proxyEnvironment[$key] = $value;
                putenv((string) $key);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->proxyEnvironment as $key => $value) {
            putenv($key . '=' . $value);
        }
    }

    private function config(): GuardConfig
    {
        $endpoint = [
            'origin'       => 'http://erp.test:8090',
            'allowedCidrs' => ['10.23.4.12/32'],
            'methods'      => ['POST'],
            'purpose'      => 'Synthetic DNS transport regression',
            'owner'        => 'Test maintainers',
        ];

        return GuardConfig::fromArray(['endpoints' => ['erp' => $endpoint]]);
    }

    private function binding(
        GuardConfig $config,
        DnsQueryInterface $query,
        ?string $profile = null,
        ?StaticThenDnsResolver $resolver = null,
    ): array {
        $clock    = new DnsTransportClock();
        $registry = new PolicyRegistry($config, $clock);
        $resolver ??= new StaticThenDnsResolver($config, $query, $clock);
        $engine = new PolicyEngine(
            new TargetNormalizer(),
            new AddressClassifier(),
            $resolver,
            $registry,
            $clock,
            new NullDecisionReporter(),
        );
        $factory = new GuardedClientFactory($engine, $config, $registry);

        return [$factory->createTransport([], $profile), $engine, $registry, $resolver];
    }

    private function counters(): array
    {
        $result = [];
        foreach (['public-a' => '203.0.115.100', 'public-b' => '203.0.115.101', 'private' => '10.23.4.12'] as $label => $ip) {
            $handle = curl_init('http://' . $ip . ':8091/stats');
            curl_setopt_array($handle, [CURLOPT_PROXY => '', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2]);
            $body = curl_exec($handle);
            unset($handle);
            if (!is_string($body)) {
                throw new RuntimeException('Prepare owned synthetic wire targets before DNS transport tests');
            }
            $result[$label] = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        return $result;
    }

    private function denied(GuardedClientBinding $binding, string $uri, string $method, string $reason): void
    {
        static $sequence = 0;
        $before          = $this->counters();
        $caught          = null;
        try {
            $binding->client->send(new Request($method, $uri));
        } catch (Throwable $error) {
            $caught = $error;
        }
        $transport = $binding->driver->counters();
        $after     = $this->counters();
        $directory = getenv('HTTP_GUARD_DNS_WITNESS_DIRECTORY');
        if (is_string($directory) && $directory !== '') {
            file_put_contents(
                $directory . '/dns_denial_' . ++$sequence . '.json',
                json_encode(
                    [
                        'test'           => $this->name(),
                        'expectedReason' => $reason,
                        'actualReason'   => $caught instanceof PolicyException ? $caught->reasonCode() : null,
                        'errorClass'     => $caught ? get_class($caught) : null,
                        'transport'      => $transport,
                        'before'         => $before,
                        'after'          => $after,
                    ],
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
                ),
            );
        }
        self::assertInstanceOf(PolicyException::class, $caught, 'DNS failure must stop before native transfer');
        self::assertSame($reason, $caught->reasonCode());
        self::assertSame(0, $transport['nativeConstructed']);
        self::assertSame(0, $transport['active']);
        foreach ($before as $label => $counts) {
            self::assertSame($counts['tcp'], $after[$label]['tcp'], $label . ' TCP');
            self::assertSame($counts['requests'], $after[$label]['requests'], $label . ' HTTP');
        }
    }

    public function testInvalidEmptyIncompleteAndNegativeDnsCannotUseEndpointPermission(): void
    {
        $cases = [
            'empty'      => static fn (string $name, int $type): DnsAnswer => new DnsAnswer([], 'fixture', true),
            'incomplete' => static fn (string $name, int $type): DnsAnswer => new DnsAnswer([], 'fixture', false),
            'invalid-ip' => static fn (string $name, int $type): DnsAnswer => new DnsAnswer(
                [['host' => rtrim($name, '.'), 'type' => 'A', 'ttl' => 1, 'ip' => '10.23.4.999']],
                'fixture',
                true,
            ),
            'wrong-family' => static fn (string $name, int $type): DnsAnswer => new DnsAnswer(
                [['host' => rtrim($name, '.'), 'type' => 'AAAA', 'ttl' => 1, 'ipv6' => '10.23.4.12']],
                'fixture',
                true,
            ),
        ];
        foreach ([2 => 'servfail', 3 => 'nxdomain'] as $rcode => $case) {
            $cases[$case] = static function (string $name, int $type) use ($rcode): DnsAnswer {
                $codec    = new DnsPacketCodec();
                $question = $codec->query($name, $type, 7331);
                $packet   = pack('nnnnnn', 7331, 0x8180 | $rcode, 1, 0, 0, 0) . substr($question, 12);
                $codec->decode($packet, 7331, $name, $type);
                throw new LogicException('Negative DNS accepted');
            };
        }
        foreach ($cases as $answer) {
            [$binding] = $this->binding($this->config(), new DnsTransportQuery($answer), 'erp');
            $this->denied($binding, 'http://erp.test:8090/echo', 'POST', 'resolution_unverified');
        }
    }

    public function testCnamePrivateHopCannotCreatePublicTransfer(): void
    {
        $query = new DnsTransportQuery(
            static function (string $name, int $type): DnsAnswer {
                $rows = [];
                if ($name === 'guard.test.' && $type === 5) {
                    $rows = [['host' => 'guard.test', 'type' => 'CNAME', 'ttl' => 5, 'target' => 'alias.test']];
                }
                if ($name === 'alias.test.' && $type === 1) {
                    $rows = [['host' => 'alias.test', 'type' => 'A', 'ttl' => 5, 'ip' => '10.23.4.12']];
                }

                return new DnsAnswer($rows, 'fixture', true);
            },
        );
        [$binding] = $this->binding(GuardConfig::fromArray([]), $query);
        $this->denied($binding, 'http://guard.test:8090/echo', 'GET', 'address_forbidden');
        self::assertSame(6, $query->calls);
    }

    public function testDnsCycleHopAndAddressLimitsHaveZeroNativeAndWireContact(): void
    {
        foreach (['cycle', 'nine-hops', 'sixty-five-addresses'] as $case) {
            $query = new DnsTransportQuery(
                static function (string $name, int $type) use ($case): DnsAnswer {
                    $owner = rtrim($name, '.');
                    $rows  = [];
                    if ($case === 'cycle' && $type === 5) {
                        $rows = [
                            [
                                'host'   => $owner,
                                'type'   => 'CNAME',
                                'ttl'    => 5,
                                'target' => $owner === 'n0.test' ? 'n1.test' : 'n0.test',
                            ],
                        ];
                    }
                    if ($case === 'nine-hops' && $type === 5) {
                        $n    = (int) substr($name, 1, strpos($name, '.') - 1);
                        $rows = [['host' => $owner, 'type' => 'CNAME', 'ttl' => 5, 'target' => 'n' . ($n + 1) . '.test']];
                    }
                    if ($case === 'sixty-five-addresses' && $type === 1) {
                        for ($n = 1; $n <= 65; ++$n) {
                            $rows[] = ['host' => $owner, 'type' => 'A', 'ttl' => 5, 'ip' => '8.8.4.' . $n];
                        }
                    }

                    return new DnsAnswer($rows, 'fixture', true);
                },
            );
            [$binding] = $this->binding(GuardConfig::fromArray([]), $query);
            $this->denied($binding, 'http://n0.test:8090/echo', 'GET', 'resolution_limit');
        }
    }

    public function testMemoHitRechecksOperatorDenyAndCannotReuseOldRegistryContext(): void
    {
        $query = new DnsTransportQuery(
            static fn (string $name, int $type): DnsAnswer => new DnsAnswer(
                $type === 1 ? [['host' => rtrim($name, '.'), 'type' => 'A', 'ttl' => 120, 'ip' => '203.0.115.100']] : [],
                'fixture',
                true,
            ),
        );
        $initial                                = GuardConfig::fromArray([]);
        [, $oldEngine, $oldRegistry, $resolver] = $this->binding($initial, $query);
        $request                                = new Request('GET', 'http://memo.test:8090/echo');
        $oldContext                             = $oldRegistry->newContext();
        self::assertSame('allow', $oldEngine->evaluate($request, $oldContext)->decision);
        self::assertSame(3, $query->calls);
        $changed               = GuardConfig::fromArray(['deniedCidrs' => ['203.0.115.100/32']]);
        [$binding, $newEngine] = $this->binding($changed, $query, null, $resolver);
        self::assertNotSame($initial->revision, $changed->revision);
        $this->denied($binding, 'http://memo.test:8090/echo', 'GET', 'address_forbidden');
        self::assertSame(3, $query->calls);
        self::assertSame('grant_invalid', $newEngine->evaluate($request, $oldContext)->reasonCode);
        self::assertSame(3, $query->calls);
    }

    public function testNssOnlyPrivateNameWithoutStaticAssignmentNeverFallsBack(): void
    {
        self::assertContains(
            '10.23.4.12',
            gethostbynamel('nss-only-guard.test') ?: [],
            'Execute with Docker --add-host=nss-only-guard.test:10.23.4.12',
        );
        $profile = [
            'origin'       => 'http://nss-only-guard.test:8090',
            'allowedCidrs' => ['10.23.4.12/32'],
            'methods'      => ['POST'],
            'purpose'      => 'Synthetic NSS negative regression',
            'owner'        => 'Test maintainers',
        ];
        $config = GuardConfig::fromArray(['endpoints' => ['nss' => $profile]]);
        $query  = new DnsTransportQuery(
            static fn (string $name, int $type): DnsAnswer => new DnsAnswer([], 'controlled-empty', true),
        );
        [$binding] = $this->binding($config, $query, 'nss');
        $this->denied($binding, 'http://nss-only-guard.test:8090/echo', 'POST', 'resolution_unverified');
        self::assertSame(3, $query->calls);
    }

    public function testCurlOptionSetFailureCannotStartAnUnpinnedTransfer(): void
    {
        $result = $this->isolatedProbe('curl_option_failure');
        self::assertSame(1, $result['transport']['nativeConstructed']);
        self::assertGreaterThan(0, $result['forcedResolveSetFailures']);
    }

    public function testUnknownComposerTupleIsRejectedBeforeNativeOrWireContact(): void
    {
        $result = $this->isolatedProbe('dependency_mismatch');
        self::assertSame('transport_unsupported', $result['reasonCode']);
        self::assertSame(0, $result['transport']['nativeConstructed']);
    }

    private function isolatedProbe(string $mode): array
    {
        $before  = $this->counters();
        $command = [PHP_BINARY];
        if ($mode === 'missing_curl') {
            $command[] = '-n';
        } elseif ($mode === 'missing_curl_multi') {
            $command[] = '-d';
            $command[] = 'disable_functions=curl_multi_exec';
        }
        $command[] = __DIR__ . '/Fixtures/IsolatedCapabilityProbe.php';
        $command[] = $mode;
        // Trusted PHP binary and committed fixture argv; no shell or request-controlled command.
        // nosemgrep: php.lang.security.exec-use.exec-use
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit  = proc_close($process);
        $after = $this->counters();
        self::assertSame(0, $exit, $stderr . ' ' . $stdout);
        $result = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('PASS', $result['status']);
        self::assertTrue($result['zeroNewTcpAndHttp']);
        self::assertSame(0, $result['transport']['active']);
        foreach ($before as $label => $counts) {
            self::assertSame($counts['tcp'], $after[$label]['tcp'], $label . ' parent TCP');
            self::assertSame($counts['requests'], $after[$label]['requests'], $label . ' parent HTTP');
        }
        $result['parentBefore'] = $before;
        $result['parentAfter']  = $after;
        $directory              = getenv('HTTP_GUARD_DNS_WITNESS_DIRECTORY');
        if (is_string($directory) && $directory !== '') {
            file_put_contents(
                $directory . '/' . $mode . '.json',
                json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            );
        }

        return $result;
    }

    public function testMissingMultiCannotUseAnUncheckedStreamTransport(): void
    {
        $result = $this->isolatedProbe('missing_curl_multi');
        self::assertFalse($result['curlMultiAvailable']);
        self::assertSame('transport_unsupported', $result['reasonCode']);
        self::assertSame(0, $result['transport']['nativeConstructed']);
    }

    public function testActualIncomingProxyHeaderIsNotProcessProxyAuthority(): void
    {
        $listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        self::assertIsResource($listener);
        $address = stream_socket_get_name($listener, false);
        fclose($listener);
        self::assertIsString($address);
        // Owned loopback listener and committed PHP fixture argv; no shell command string.
        // nosemgrep: php.lang.security.exec-use.exec-use
        $process = proc_open(
            [PHP_BINARY, '-S', $address, __DIR__ . '/Fixtures/IncomingProxyHeader.php'],
            [['pipe', 'r'], ['file', '/dev/null', 'a'], ['file', '/dev/null', 'a']],
            $pipes,
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        try {
            $ready = false;
            for ($attempt = 0; $attempt < 40; ++$attempt) {
                $handle = curl_init('http://' . $address . '/ready');
                curl_setopt_array(
                    $handle,
                    [
                        CURLOPT_PROXY             => '',
                        CURLOPT_RETURNTRANSFER    => true,
                        CURLOPT_CONNECTTIMEOUT_MS => 50,
                        CURLOPT_TIMEOUT_MS        => 100,
                    ],
                );
                $body = curl_exec($handle);
                unset($handle);
                if ($body === 'ready') {
                    $ready = true;
                    break;
                }
                usleep(50000);
            }
            self::assertTrue($ready, 'Owned PHP SAPI fixture must start');
            $before = $this->counters();
            $handle = curl_init('http://' . $address . '/proxy-header');
            curl_setopt_array(
                $handle,
                [
                    CURLOPT_PROXY          => '',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 5,
                    CURLOPT_HTTPHEADER     => ['Proxy: http://10.23.4.12:8080'],
                ],
            );
            $body   = curl_exec($handle);
            $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            unset($handle);
            $after = $this->counters();
            self::assertIsString($body);
            $result                 = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $result['parentBefore'] = $before;
            $result['parentAfter']  = $after;
            $directory              = getenv('HTTP_GUARD_DNS_WITNESS_DIRECTORY');
            if (is_string($directory) && $directory !== '') {
                file_put_contents(
                    $directory . '/incoming_proxy_header.json',
                    json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
                );
            }
            self::assertSame(200, $status, $body);
            self::assertSame('PASS', $result['status']);
            self::assertSame('cli-server', $result['sapi']);
            self::assertTrue($result['incomingProxyHeaderPresent']);
            self::assertSame([], $result['processProxyNames']);
            self::assertSame(1, $result['transport']['nativeConstructed']);
            self::assertSame(0, $result['transport']['active']);
            self::assertSame('public-a', $result['targetLabel']);
            self::assertSame('guard.test:8090', $result['targetHost']);
            foreach ($before as $label => $counts) {
                $expected = $label === 'public-a' ? 1 : 0;
                self::assertSame($counts['tcp'] + $expected, $after[$label]['tcp'], $label . ' SAPI TCP');
                self::assertSame($counts['requests'] + $expected, $after[$label]['requests'], $label . ' SAPI HTTP');
            }
        } finally {
            proc_terminate($process);
            proc_close($process);
        }
    }
}
