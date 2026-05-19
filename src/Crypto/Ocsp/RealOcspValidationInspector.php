<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationInspector
{
    /**
     * @return array<string,mixed>
     */
    public function inspect(
        string $certificatePem,
        string $issuerCertificatePem
    ): array {
        $result = (
            new RealOcspValidator()
        )->validate(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return [
            'successful' => $result->successful,
            'good' => $result->isGood(),
            'revoked' => $result->isRevoked(),
            'unknown' => $result->isUnknown(),
            'certificate_status' => $result->certificateStatus,
        ];
    }
}