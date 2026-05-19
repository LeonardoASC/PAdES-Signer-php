<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationMaterial
{
    /**
     * @param array<string> $certificatesDer
     * @param array<string> $ocspResponsesDer
     * @param array<string> $crlsDer
     */
    public function __construct(
        public array $certificatesDer = [],
        public array $ocspResponsesDer = [],
        public array $crlsDer = []
    ) {}

    public function hasRevocationData(): bool
    {
        return $this->ocspResponsesDer !== []
            || $this->crlsDer !== [];
    }

    public function hasCertificates(): bool
    {
        return $this->certificatesDer !== [];
    }
}