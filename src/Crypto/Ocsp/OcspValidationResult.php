<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspValidationResult
{
    public function __construct(
        public bool $successful,
        public ?string $certificateStatus
    ) {}

    public function isGood(): bool
    {
        return $this->successful
            && $this->certificateStatus === 'good';
    }

    public function isRevoked(): bool
    {
        return $this->certificateStatus === 'revoked';
    }

    public function isUnknown(): bool
    {
        return $this->certificateStatus === 'unknown';
    }
}