<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class RevocationMaterial
{
    /**
     * @param array<string> $ocspResponsesDer
     * @param array<string> $crlsDer
     */
    public function __construct(
        public array $ocspResponsesDer = [],
        public array $crlsDer = []
    ) {}

    public function hasRevocationData(): bool
    {
        return $this->ocspResponsesDer !== []
            || $this->crlsDer !== [];
    }
}
