<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationHealth
{
    /**
     * @return array<string,mixed>
     */
    public function check(
        string $certificatePem,
        string $issuerCertificatePem
    ): array {
        $status = (
            new RealOcspValidationStatus()
        )->status(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return [
            'healthy' => (
                $status === 'good'
            ),

            'status' => $status,

            'available' => (
                $status !== 'unavailable'
            ),
        ];
    }
}