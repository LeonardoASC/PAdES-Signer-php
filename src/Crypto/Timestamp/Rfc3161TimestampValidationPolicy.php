<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

use NihilLabs\Pades\Crypto\X509\TrustStoreInterface;

final readonly class Rfc3161TimestampValidationPolicy
{
    /**
     * @param array<string> $allowedPolicyOids
     */
    public function __construct(
        public ?string $expectedMessageImprint = null,
        public array $allowedPolicyOids = [],
        public ?TrustStoreInterface $tsaTrustStore = null,
        public bool $requireTsaChainValidation = false
    ) {}
}
