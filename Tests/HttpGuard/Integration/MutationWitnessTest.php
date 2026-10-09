<?php

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Integration;

use GuzzleHttp\Promise\PromiseInterface;
use Netresearch\HttpGuard\{AddressClassifier, GuardConfig, NullDecisionReporter, PolicyEngine, PolicyException, PolicyRegistry, TargetNormalizer};
use Netresearch\HttpGuard\Client\GuardedClientFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/ProductionTransportTest.php';
/** Mutation witnesses record native/leaf counters and independent target TCP/HTTP counters before assertions. */
final class MutationWitnessTest extends TestCase
{
    public static function vectors(): iterable
    {
        foreach ([
            'address_check_removed',
            'pin_removed',
            'fallback_enabled',
            'proxy_passthrough',
            'stream_passthrough',
            'grant_binding_removed',
        ] as $case) {
            $selected = getenv('HTTP_GUARD_MUTATION_CASE');
            if ($selected !== false && $selected !== '' && $selected !== $case) {
                continue;
            }
            yield $case => [$case];
        }
    }

    private function counts(): array
    {
        $r = [];
        foreach (['public-a' => '203.0.115.100', 'public-b' => '203.0.115.101', 'private' => '10.23.4.12'] as $label => $ip) {
            $h = curl_init('http://' . $ip . ':8091/stats');
            curl_setopt_array($h, [CURLOPT_RETURNTRANSFER => true, CURLOPT_PROXY => '', CURLOPT_TIMEOUT => 2]);
            $s = curl_exec($h);
            if (!is_string($s)) {
                throw new RuntimeException('Missing synthetic target');
            }
            $v         = json_decode($s, true, 512, JSON_THROW_ON_ERROR);
            $r[$label] = ['tcp' => $v['tcp'], 'http' => $v['requests']];
        }

        return $r;
    }

    #[DataProvider('vectors')]
    public function testCriticalMutationHasNativeAndWireWitness(string $case): void
    {
        $saved = [];
        foreach (getenv(null, true) as $key => $value) {
            if (in_array(strtolower((string) $key), ['http_proxy', 'https_proxy', 'all_proxy', 'no_proxy'], true)) {
                $saved[$key] = $value;
                putenv((string) $key);
            }
        }
        try {
            $config = GuardConfig::fromArray(
                [
                    'endpoints' => [
                        'erp' => [
                            'origin'       => 'http://guard.test:8090',
                            'allowedCidrs' => ['10.23.4.12/32'],
                            'methods'      => ['POST'],
                            'purpose'      => 'Synthetic mutant witness',
                            'owner'        => 'Test maintainers',
                        ],
                    ],
                ],
            );
            $clock                           = new TransportClock();
            $registry                        = new PolicyRegistry($config, $clock);
            $resolver                        = new TransportResolver();
            $resolver->answers['guard.test'] = [
                $case === 'address_check_removed' ? ['203.0.115.100', '10.23.4.12'] : ($case === 'fallback_enabled' ? ['203.0.115.1'] : ($case === 'grant_binding_removed' ? ['10.23.4.12'] : ['203.0.115.100'])),
            ];
            $engine = new PolicyEngine(
                new TargetNormalizer(),
                new AddressClassifier(),
                $resolver,
                $registry,
                $clock,
                new NullDecisionReporter(),
            );
            $leafCalls = 0;
            $native    = \GuzzleHttp\Utils::chooseHandler();
            $leaf      = static function ($request, array $options) use (&$leafCalls, $native): PromiseInterface {
                ++$leafCalls;

                return $native($request, $options);
            };
            $factory = new GuardedClientFactory($engine, $config, $registry, new TransportStackProvider([], [], $leaf));
            $binding = $factory->createTransport(
                ['connect_timeout' => 0.1, 'timeout' => 0.3],
                $case === 'grant_binding_removed' ? 'erp' : null,
            );
            $before  = $this->counts();
            $options = match ($case) {
                'proxy_passthrough'  => ['proxy' => 'http://203.0.115.101:8090'],
                'stream_passthrough' => ['stream' => true],
                default              => [],
            };
            $outcome = '';
            $reason  = null;
            $label   = null;
            try {
                $response = $binding->client->request('GET', 'http://guard.test:8090/echo', $options);
                $label    = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)['label'];
                $outcome  = 'response';
            } catch (PolicyException $error) {
                $outcome = 'policy_denied';
                $reason  = $error->reasonCode();
            } catch (\GuzzleHttp\Exception\ConnectException) {
                $outcome = 'connect_failed';
            }
            $after  = $this->counts();
            $record = [
                'case'           => $case,
                'outcome'        => $outcome,
                'reason'         => $reason,
                'response_label' => $label,
                'resolver_calls' => $resolver->calls,
                'transport'      => $binding->driver->counters(),
                'leaf_calls'     => $leafCalls,
                'before'         => $before,
                'after'          => $after,
            ];
            $directory = getenv('HTTP_GUARD_WITNESS_DIRECTORY');
            if (is_string($directory) && $directory !== '') {
                if (!is_dir($directory)) {
                    mkdir($directory, 0770, true);
                }
                file_put_contents(
                    $directory . '/' . $case . '.json',
                    json_encode($record, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
                );
            }
            if ($case === 'pin_removed') {
                self::assertSame('response', $outcome);
                self::assertSame('public-a', $label);
                self::assertSame($before['public-a']['http'] + 1, $after['public-a']['http']);
                self::assertSame($before['private'], $after['private']);
            } elseif ($case === 'fallback_enabled') {
                self::assertSame('connect_failed', $outcome);
                self::assertSame($before, $after);
            } else {
                self::assertSame('policy_denied', $outcome);
                self::assertSame($before, $after);
            }
            self::assertSame(0, $leafCalls);
            self::assertSame(0, $binding->driver->counters()['active']);
        } finally {
            foreach ($saved as $key => $value) {
                putenv($key . '=' . $value);
            }
        }
    }
}
