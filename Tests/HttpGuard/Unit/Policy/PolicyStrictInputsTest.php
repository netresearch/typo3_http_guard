<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{GuardConfig, PolicyException, TargetNormalizer, PolicyRegistry, PolicyEngine, AddressClassifier, ResolverInterface, Resolution, SystemClock, NullDecisionReporter};
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
final class PolicyStrictInputsTest extends TestCase
{
    public function testAllRawUriCorpusCasesThroughPreconstructionSeam(): void
    {
        $corpus = json_decode(
            file_get_contents(
                dirname(__DIR__, 4) . '/Resources/Private/HttpGuard/data/security-corpus/uri-cases.json'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $normalizer = new TargetNormalizer();
        foreach ($corpus['cases'] as $case) {
            try {
                $normalizer->assertRawUri($case['input']['raw_uri']);
                $target = $normalizer->normalize(
                    new Request('GET', $case['input']['raw_uri'])
                );
            } catch (PolicyException $e) {
                self::assertSame(
                    'deny',
                    $case['expected']['decision'] ?? null,
                    $case['id']
                );
                self::assertSame(
                    $case['expected']['reason'],
                    $e->reasonCode(),
                    $case['id']
                );
                continue;
            }
            self::assertSame(
                'allow_syntax_only',
                $case['expected']['normalization'] ?? null,
                $case['id']
            );
            self::assertSame(
                $case['expected']['host'],
                $target->host,
                $case['id']
            );
            self::assertSame(
                $case['expected']['effective_port'],
                $target->port,
                $case['id']
            );
        }
    }
    public function testCompleteEndpointCorpusAsPolicyOnly(): void
    {
        $corpus = json_decode(
            file_get_contents(
                dirname(__DIR__, 4) . '/Resources/Private/HttpGuard/data/security-corpus/endpoint-cases.json'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        foreach ($corpus['cases'] as $case) {
            $in = $case['input'];
            try {
                $config = GuardConfig::fromArray(
                    [
                        'deniedCidrs' => $in['operator_denied_cidrs'],
                        'endpoints' => [
                            'fixture' => [
                                'origin' => $in['origin'],
                                'allowedCidrs' => $in['allowed_cidrs'],
                                'methods' => [$in['method']],
                                'allowLoopback' => $in['allow_loopback'],
                                'purpose' => 'synthetic corpus',
                                'owner' => 'test maintainers',
                            ],
                        ],
                    ]
                );
                $clock = new SystemClock();
                $registry = new PolicyRegistry($config, $clock);
                $resolver = new class($in['address']) implements ResolverInterface
                {
                    public function __construct(private string $ip)
                    {
                    }
                    public function resolve(string $host): Resolution
                    {
                        return new Resolution([$this->ip], 'fixture', 0, 'fixture');
                    }
                };
                $engine = new PolicyEngine(
                    new TargetNormalizer(),
                    new AddressClassifier(),
                    $resolver,
                    $registry,
                    $clock,
                    new NullDecisionReporter()
                );
                $decision = $engine->evaluate(
                    new Request($in['method'], $in['origin']),
                    $registry->newContext(
                        $in['registry_issued_client_binding'] ? 'fixture' : null
                    )
                );
                self::assertSame(
                    $case['expected']['decision'] === 'allow_policy_only' ? 'allow' : 'deny',
                    $decision->decision,
                    $case['id']
                );
                self::assertSame(
                    $case['expected']['reason'],
                    $decision->reasonCode,
                    $case['id']
                );
            } catch (PolicyException $e) {
                self::assertSame(
                    'deny',
                    $case['expected']['decision'],
                    $case['id']
                );
                self::assertSame(
                    $case['expected']['reason'],
                    $e->reasonCode(),
                    $case['id']
                );
            }
        }
    }
    public function testStrictTypesUnknownFieldsAndDatesAreRejected(): void
    {
        $base = [
            'origin' => 'https://erp.internal',
            'allowedCidrs' => ['10.23.4.0/24'],
            'methods' => ['POST'],
            'purpose' => 'orders',
            'owner' => 'ERP maintainers',
        ];
        $cases = [
            ['unexpected' => true],
            ['schemaVersion' => 1.0],
            ['mode' => true],
            ['deniedCidrs' => ['10.23.4.12/24']],
            ['deniedCidrs' => ['::ffff:10.23.4.0/120']],
            ['resolver' => ['cacheTtlSeconds' => '5']],
            ['resolver' => ['maxAddresses' => 65]],
            ['redirects' => ['max' => 11]],
            ['tls' => ['requireVerification' => 1]],
            ['logging' => ['allowedSampleRate' => INF]],
            ['logging' => ['hostMode' => 'sha256']],
            ['logging' => ['hostHmacKeyEnv' => 'SECRET-KEY']],
            [
                'resolver' => [
                    'staticHosts' => [
                        'API.EXAMPLE' => ['8.8.8.8'],
                        'api.example.' => ['8.8.4.4'],
                    ],
                ],
            ],
        ];
        foreach ([
            ['expiresAt' => 'tomorrow'],
            ['expiresAt' => '2026-10-08T00:00:60Z'],
            ['expiresAt' => '2026-10-08T24:00:00Z'],
            ['reviewAfter' => '2026-02-30'],
            ['unknownEndpointField' => true],
            ['origin' => 'https://erp.internal/path'],
            ['allowedCidrs' => ['10.23.0.0/16']],
            ['methods' => ['CONNECT']],
            ['purpose' => ''],
        ] as $changes) {
            $cases[] = ['endpoints' => ['erp' => array_replace($base, $changes)]];
        }
        foreach ($cases as $index => $config) {
            try {
                GuardConfig::fromArray($config);
                self::fail('Invalid configuration accepted at ' . $index);
            } catch (PolicyException $e) {
                self::assertSame(
                    'configuration_invalid',
                    $e->reasonCode(),
                    (string) $index
                );
            }
        }
    }
    public function testForgedContextWithBorrowedGrantAndScopeIsStillRejected(): void
    {
        $config = GuardConfig::fromArray([]);
        $clock = new SystemClock();
        $registry = new PolicyRegistry($config, $clock);
        $owned = $registry->newContext();
        $forged = new \Netresearch\HttpGuard\RequestPolicyContext(
            $owned->mode,
            $owned->policyRevision,
            $owned->clientScope,
            $owned->endpointGrant
        );
        $this->expectException(PolicyException::class);
        $registry->validateContext($forged);
    }
    public function testIpv6NetworkContainingLoopbackRequiresExactHostPrefix(): void
    {
        $endpoint = [
            'origin' => 'https://erp.internal',
            'allowedCidrs' => ['::/64'],
            'methods' => ['POST'],
            'purpose' => 'loopback regression',
            'owner' => 'test maintainers',
            'allowLoopback' => true,
        ];
        try {
            GuardConfig::fromArray(['endpoints' => ['fixture' => $endpoint]]);
            self::fail('Broad IPv6 network containing loopback was accepted');
        } catch (PolicyException $e) {
            self::assertSame('configuration_invalid', $e->reasonCode());
        }
        $endpoint['allowedCidrs'] = ['::1/128'];
        self::assertSame(
            ['::1/128'],
            GuardConfig::fromArray(['endpoints' => ['fixture' => $endpoint]])->data['endpoints']['fixture']['allowedCidrs']
        );
    }
}
