<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationReport
{
    /**
     * @return array<string,mixed>
     */
    public function generate(
        string $certificatePem,
        string $issuerCertificatePem
    ): array {
        return [
            'summary' => (
                new RealOcspValidationSummary()
            )->summarize(
                certificatePem: $certificatePem,
                issuerCertificatePem: $issuerCertificatePem
            ),

            'inspection' => (
                new RealOcspValidationInspector()
            )->inspect(
                certificatePem: $certificatePem,
                issuerCertificatePem: $issuerCertificatePem
            ),
        ];
    }
}