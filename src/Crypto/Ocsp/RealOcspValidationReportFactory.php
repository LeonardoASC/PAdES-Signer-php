<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationReportFactory
{
    /**
     * @return array<string,mixed>
     */
    public function create(
        string $certificatePem,
        string $issuerCertificatePem
    ): array {
        return (new RealOcspValidationReport())
            ->generate(
                certificatePem: $certificatePem,
                issuerCertificatePem: $issuerCertificatePem
            );
    }

    public function createJson(
        string $certificatePem,
        string $issuerCertificatePem
    ): string {
        return (new RealOcspValidationReportJsonSerializer())
            ->serialize(
                $this->create(
                    certificatePem: $certificatePem,
                    issuerCertificatePem: $issuerCertificatePem
                )
            );
    }
}