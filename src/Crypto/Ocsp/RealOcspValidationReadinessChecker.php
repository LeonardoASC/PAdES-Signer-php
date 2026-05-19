<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationReadinessChecker
{
    public function isReady(
        string $certificatePem,
        string $issuerCertificatePem
    ): bool {
        $inspection = (
            new RealOcspValidationReportInspector()
        )->inspect(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return $inspection['has_summary']
            && $inspection['has_inspection'];
    }
}