<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationDiagnostics
{
    /**
     * @return array<string,mixed>
     */
    public function diagnose(
        string $certificatePem,
        string $issuerCertificatePem
    ): array {
        $health = (
            new RealOcspValidationHealth()
        )->check(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        $inspection = (
            new RealOcspValidationInspector()
        )->inspect(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return [
            'healthy' => $health['healthy'],
            'available' => $health['available'],
            'status' => $health['status'],
            'successful' => $inspection['successful'],
            'has_summary' => ($inspection['has_summary'] ?? false),
            'has_inspection' => ($inspection['has_inspection'] ?? false),
        ];
    }
}
