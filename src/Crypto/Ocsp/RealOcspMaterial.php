<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspMaterial
{
    /**
     * @param array<string> $responderCertificatesDer
     * @param array<string> $certificateChainPem
     */
    public function __construct(
        public string $responseDer,
        public string $status,
        public string $ocspUrl,
        public string $issuerCertificatePem,
        public array $responderCertificatesDer = [],
        public array $certificateChainPem = []
    ) {}
}
