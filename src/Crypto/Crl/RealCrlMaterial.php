<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Crl;

final readonly class RealCrlMaterial
{
    /**
     * @param array<string> $crlsDer
     * @param array<string> $crlUrls
     * @param array<string> $certificateChainPem
     */
    public function __construct(
        public array $crlsDer,
        public array $crlUrls,
        public array $certificateChainPem
    ) {}
}
