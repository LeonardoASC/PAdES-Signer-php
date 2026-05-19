<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationStatus
{
    public function status(
        string $certificatePem,
        string $issuerCertificatePem
    ): string {
        $capabilities = (
            new RealOcspValidationCapabilityDetector()
        )->detect(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        if (! $capabilities['successful']) {
            return 'unavailable';
        }

        if (! $capabilities['has_status']) {
            return 'unknown';
        }

        $inspection = (
            new RealOcspValidationReportInspector()
        )->inspect(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return $inspection['status'] ?? 'unknown';
    }
}