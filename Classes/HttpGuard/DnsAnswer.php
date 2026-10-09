<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

final readonly class DnsAnswer
{
    /** Backend record data is untrusted until the resolver validates it.
     * @param array<array-key,mixed> $records
     */
    public function __construct(
        public array $records,
        public string $source,
        public bool $complete
    )
    {
    }
}
