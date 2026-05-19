<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use InvalidArgumentException;

final readonly class IcpBrasilSignaturePolicy
{
    public function __construct(
        public string $policyOid,
        public string $policyHash,
        public ?string $policyUri = null
    ) {
        if ($this->policyHash === '') {
            throw new InvalidArgumentException('Policy hash must not be empty.');
        }
    }

    public static function adRtPdfPlaceholder(
        string $policyHash,
        ?string $policyUri = null
    ): self {
        return new self(
            policyOid: '2.16.76.1.7.1.12.1.2',
            policyHash: $policyHash,
            policyUri: $policyUri
        );
    }
}
