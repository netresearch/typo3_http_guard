<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

final class AddressClassifier
{
    /** @var array<int,array<int,array<string,array{class:string,endpoint:string}>>> */
    private array $rules = [4 => [], 6 => []];
    /** @var array<int,array<string,true>> */
    private array $allocated = [];
    /** @var array<string,Cidr> */
    private array $cidrs = [];
    public function __construct()
    {
        $data = json_decode(
            (string) file_get_contents(
                dirname(__DIR__, 2) . '/Resources/Private/HttpGuard/data/security-corpus/address-rules.json'
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        foreach (array_merge(
            $data['iana_special_rules'],
            $data['supplemental_rules'],
            $data['provider_hard_denies']
        ) as $rule) {
            if (str_starts_with($rule['cidr'], '::ffff:')) {
                continue;
            }
            $network = Cidr::parse($rule['cidr'], false);
            $this->rules[$network->family][$network->prefix][$network->network] = ['class' => $rule['class'], 'endpoint' => $rule['endpoint']];
        }
        foreach ([4, 6] as $family) {
            krsort($this->rules[$family], SORT_NUMERIC);
        }
        foreach ($data['ipv6_allocated_global_prefixes'] as $rule) {
            $network = Cidr::parse($rule['cidr'], false);
            $this->allocated[$network->prefix][$network->network] = true;
        }
        krsort($this->allocated, SORT_NUMERIC);
    }
    public function classify(string $ip): AddressClassification
    {
        $canonical = Cidr::address($ip);
        $packed = (string) inet_pton($canonical);
        $family = strlen($packed) === 4 ? 4 : 6;
        foreach ($this->rules[$family] as $prefix => $networks) {
            $rule = $networks[Cidr::networkBits($packed, $prefix)] ?? null;
            if ($rule === null) {
                continue;
            }
            $exceptable = $rule['endpoint'] !== 'forbidden';
            return new AddressClassification(
                $canonical,
                $rule['class'],
                false,
                $exceptable,
                !$exceptable
            );
        }
        if ($family === 6) {
            $allocated = false;
            foreach ($this->allocated as $prefix => $networks) {
                if (isset($networks[Cidr::networkBits($packed, $prefix)])) {
                    $allocated = true;
                    break;
                }
            }
            if (!$allocated) {
                return new AddressClassification(
                    $canonical,
                    'unallocated_or_non_global_ipv6',
                    false,
                    false,
                    true
                );
            }
        }
        return new AddressClassification(
            $canonical,
            'ordinary_global_unicast',
            true,
            true,
            false
        );
    }
    public function contains(string $canonicalCidr, string $canonicalIp): bool
    {
        $network = $this->cidrs[$canonicalCidr] ??= Cidr::parse($canonicalCidr);
        $packed = @inet_pton($canonicalIp);
        if ($packed === false) {
            return false;
        }
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat(chr(0), 10) . chr(255) . chr(255)) {
            $packed = substr($packed, 12);
        }
        return $network->containsPacked($packed);
    }
}
