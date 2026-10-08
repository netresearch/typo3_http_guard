<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{GuardConfig, AddressClassifier, PolicyException, TargetNormalizer};
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
final class PolicyCorpusTest extends TestCase
{
    public function testEntireBinaryAddressCorpus(): void
    {
        $classifier = new AddressClassifier();
        $cases = json_decode(
            file_get_contents(
                __DIR__ . '/../../../data/security-corpus/address-cases.json'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        foreach ($cases['cases'] as $case) {
            $result = $classifier->classify($case['input']['address']);
            self::assertSame(
                $case['expected']['normalized_address'],
                $result->canonicalIp,
                $case['id']
            );
            self::assertSame(
                $case['expected']['public_decision'] === 'allow',
                $result->public,
                $case['id']
            );
            self::assertSame(
                $case['expected']['address_class'],
                $result->addressClass,
                $case['id']
            );
        }
    }
    public function testMappedLiteralAndMatchingHostRemainSameAuthority(): void
    {
        $target = (new TargetNormalizer())->normalize(
            new Request(
                'POST',
                'HTTPS://[::ffff:10.23.4.12]:8443/path?secret=keep'
            )
        );
        self::assertSame('https://[::ffff:10.23.4.12]:8443', $target->origin);
        self::assertSame('10.23.4.12', $target->literalIp);
        self::assertSame(
            '[::ffff:10.23.4.12]:8443',
            $target->canonicalRequest->getHeaderLine('Host')
        );
    }
    public function testHostMismatchIsRejected(): void
    {
        $this->expectException(PolicyException::class);
        (new TargetNormalizer())->normalize(
            new Request(
                'GET',
                'https://api.example:8443',
                ['Host' => 'api.example']
            )
        );
    }
    public function testCanonicalHostnameAndDefaultPortNormalizeTogether(): void
    {
        $target = (new TargetNormalizer())->normalize(
            new Request(
                'GET',
                'https://API.EXAMPLE./a',
                ['Host' => 'api.example:443']
            )
        );
        self::assertSame('https://api.example', $target->origin);
        self::assertSame(
            'api.example',
            $target->canonicalRequest->getHeaderLine('Host')
        );
    }
    public function testStrictConfigurationRejectsUnknownNestedField(): void
    {
        $this->expectException(PolicyException::class);
        GuardConfig::fromArray(['resolver' => ['fallbackNss' => true]]);
    }
    public function testStrictConfigurationRejectsImpossibleExpiryDate(): void
    {
        $this->expectException(PolicyException::class);
        GuardConfig::fromArray(
            [
                'endpoints' => [
                    'erp' => [
                        'origin' => 'https://erp.internal',
                        'allowedCidrs' => ['10.23.4.0/24'],
                        'methods' => ['POST'],
                        'purpose' => 'orders',
                        'owner' => 'ERP maintainers',
                        'expiresAt' => '2026-02-30T12:00:00Z',
                    ],
                ],
            ]
        );
    }
}
