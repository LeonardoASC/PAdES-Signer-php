<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationSummary
{
    public function summarize(
        string $certificatePem,
        string $issuerCertificatePem
    ): string {
        $inspection = (
            new RealOcspValidationInspector()
        )->inspect(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return sprintf(
            'successful=%s | status=%s | good=%s | revoked=%s | unknown=%s',
            $inspection['successful']
                ? 'yes'
                : 'no',

            $inspection['certificate_status']
                ?? 'null',

            $inspection['good']
                ? 'yes'
                : 'no',

            $inspection['revoked']
                ? 'yes'
                : 'no',

            $inspection['unknown']
                ? 'yes'
                : 'no'
        );
    }
}