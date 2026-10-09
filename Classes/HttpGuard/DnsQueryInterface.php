<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

interface DnsQueryInterface
{
    public function query(string $absoluteFqdn, int $qtype): DnsAnswer;
}
