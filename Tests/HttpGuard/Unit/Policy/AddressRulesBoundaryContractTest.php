<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{AddressClassifier, PolicyException};
use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\AddressRulesBoundaryState as State;
use PHPUnit\Framework\Attributes\{DataProvider, PreserveGlobalState, RunTestsInSeparateProcesses};
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AddressRulesBoundaryContractTest extends TestCase
{
    #[DataProvider('malformedRuleBundles')]
    public function testTrustedRuleBundleMustBeCompleteTypedAndValidBeforeClassification(string $case): void
    {
        $data = json_decode(
            file_get_contents(
                dirname(__DIR__, 4) . '/Resources/Private/HttpGuard/data/security-corpus/address-rules.json',
            ),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        require __DIR__ . '/Fixtures/AddressRulesBoundaryShim.php';
        if ($case === 'missing') {
            State::$exists = false;
        } elseif ($case === 'unreadable') {
            State::$payload = false;
        } elseif ($case === 'invalid-json') {
            State::$payload = '{invalid fixture JSON';
        } else {
            if ($case === 'root-scalar') {
                $data = true;
            } elseif ($case === 'schema-string') {
                $data['schema_version'] = '1';
            } elseif (str_starts_with($case, 'section-')) {
                [$section, $shape] = explode(':', substr($case, 8));
                if ($shape === 'missing') {
                    unset($data[$section]);
                } elseif ($shape === 'empty') {
                    $data[$section] = [];
                } elseif ($shape === 'associative') {
                    $data[$section] = ['named' => $data[$section][0]];
                } else {
                    $data[$section] = false;
                }
            } elseif (str_starts_with($case, 'allocated-')) {
                $data['ipv6_allocated_global_prefixes'][0] = match (substr($case, 10)) {
                    'scalar'    => false,
                    'cidr-type' => ['cidr' => false],
                    'IPv4'      => ['cidr' => '8.8.8.0/24'],
                    default     => ['cidr' => 'bad'],
                };
            } else {
                $row = $data['iana_special_rules'][0];
                switch ($case) {
                    case 'row-scalar':
                        $row = false;
                        break;
                    case 'missing-cidr':
                        unset($row['cidr']);
                        break;
                    case 'cidr-type':
                        $row['cidr'] = false;
                        break;
                    case 'class-type':
                        $row['class'] = false;
                        break;
                    case 'class-empty':
                        $row['class'] = '';
                        break;
                    case 'class-newline':
                        $row['class'] = "private_ipv4\n";
                        break;
                    case 'class-space':
                        $row['class'] = 'bad class';
                        break;
                    case 'class-65':
                        $row['class'] = str_repeat('a', 65);
                        break;
                    case 'endpoint-type':
                        $row['endpoint'] = false;
                        break;
                    case 'endpoint-unknown':
                        $row['endpoint'] = 'implicitly_allowed';
                        break;
                }
                $data['iana_special_rules'][0] = $row;
            }
            State::$payload = json_encode($data, JSON_THROW_ON_ERROR);
        }
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        new AddressClassifier();
    }

    public static function malformedRuleBundles(): iterable
    {
        foreach ([
            'missing',
            'unreadable',
            'invalid-json',
            'root-scalar',
            'schema-string',
            'row-scalar',
            'missing-cidr',
            'cidr-type',
            'class-type',
            'class-empty',
            'class-newline',
            'class-space',
            'class-65',
            'endpoint-type',
            'endpoint-unknown',
            'allocated-scalar',
            'allocated-cidr-type',
            'allocated-IPv4',
            'allocated-bad',
        ] as $case) {
            yield $case => [$case];
        }
        foreach (['iana_special_rules', 'supplemental_rules', 'provider_hard_denies', 'ipv6_allocated_global_prefixes'] as $section) {
            foreach (['missing', 'empty', 'associative', 'scalar'] as $shape) {
                yield $section . $shape => ['section-' . $section . ':' . $shape];
            }
        }
    }

    public function testCompleteTrustedBundleKeepsPublicPrivateAndHardDeniedClassification(): void
    {
        $payload = file_get_contents(dirname(__DIR__, 4) . '/Resources/Private/HttpGuard/data/security-corpus/address-rules.json');
        require __DIR__ . '/Fixtures/AddressRulesBoundaryShim.php';
        State::$payload = $payload;
        $classifier     = new AddressClassifier();
        self::assertTrue($classifier->classify('203.0.115.7')->public);
        self::assertFalse($classifier->classify('10.23.4.12')->public);
        self::assertTrue($classifier->classify('10.23.4.12')->endpointExceptable);
        self::assertTrue($classifier->classify('169.254.169.254')->hardDenied);
    }

    public function testTrustedRuleFingerprintChangesPolicyRevisionWithoutChangingOperatorData(): void
    {
        require __DIR__ . '/Fixtures/AddressRulesBoundaryShim.php';
        $first            = \Netresearch\HttpGuard\GuardConfig::fromArray([]);
        State::$rulesHash = str_repeat('b', 64);
        $second           = \Netresearch\HttpGuard\GuardConfig::fromArray([]);
        self::assertSame($first->data, $second->data);
        self::assertNotSame(
            $first->revision,
            $second->revision,
            'Existing contexts must become stale when trusted address rules change',
        );
    }

    public function testUnreadableRuleFingerprintFailsClosedBeforeConfigurationIsIssued(): void
    {
        require __DIR__ . '/Fixtures/AddressRulesBoundaryShim.php';
        State::$rulesHash = false;
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('configuration_invalid');
        \Netresearch\HttpGuard\GuardConfig::fromArray([]);
    }
}
