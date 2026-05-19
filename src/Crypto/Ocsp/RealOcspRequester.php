<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspRequester
{
    public function request(
        string $certificatePem,
        string $issuerCertificatePem
    ): ?string {
        $url = (
            new OcspUrlResolver()
        )->resolve(
            $certificatePem
        );

        if ($url === null) {
            return null;
        }

        $request = (
            new RealOcspRequestFactory()
        )->build(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return (
            new OcspClient()
        )->request(
            $url,
            $request
        );
    }
}