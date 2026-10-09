<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard;

final readonly class AddressClassification
{
    public function __construct(
        public string $canonicalIp,
        public string $addressClass,
        public bool $public,
        public bool $endpointExceptable,
        public bool $hardDenied
    )
    {
    }
}
