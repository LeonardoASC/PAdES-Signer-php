<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidator
{
    public function validate(
        string $certificatePem,
        string $issuerCertificatePem
    ): OcspValidationResult {
        $response = (
            new RealOcspRequester()
        )->request(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        if ($response === null) {
            return new OcspValidationResult(
                successful: false,
                certificateStatus: null
            );
        }

        return (new OcspValidator())
            ->validate($response);
    }
}