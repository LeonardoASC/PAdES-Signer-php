<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationReportInspector
{
    /**
     * @return array<string,mixed>
     */
    public function inspect(
        string $certificatePem,
        string $issuerCertificatePem
    ): array {
        $report = (
            new RealOcspValidationReportFactory()
        )->create(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return [
            'has_summary' => isset(
                $report['summary']
            ),

            'has_inspection' => isset(
                $report['inspection']
            ),

            'successful' => (
                $report['inspection']['successful']
                ?? false
            ),

            'status' => (
                $report['inspection']['certificate_status']
                ?? null
            ),
        ];
    }
}