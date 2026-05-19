<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\X509\X509CertificateInfoExtractor;

final readonly class RealOcspRequestFactory
{
    public function build(
        string $certificatePem,
        string $issuerCertificatePem
    ): string {
        $info = (
            new X509CertificateInfoExtractor()
        )->extract(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        return (
            new OcspRequestBuilder()
        )->build(
            issuerNameDer: $info['issuerNameDer'],
            issuerPublicKeyDer: $info['issuerPublicKeyDer'],
            serialNumberHex: $info['serialNumberHex']
        );
    }
}