<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationCapabilityDetector
{
    /**
     * @return array<string,bool>
     */
    public function detect(
        string $certificatePem,
        string $issuerCertificatePem
    ): array {
        $inspection = (
            new RealOcspValidationReportInspector()
        )->inspect(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return [
            'has_summary' => (
                $inspection['has_summary']
            ),

            'has_inspection' => (
                $inspection['has_inspection']
            ),

            'successful' => (
                $inspection['successful']
            ),

            'has_status' => (
                $inspection['status'] !== null
            ),
        ];
    }
}