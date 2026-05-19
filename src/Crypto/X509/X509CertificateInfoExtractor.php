<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use RuntimeException;

final readonly class X509CertificateInfoExtractor
{
    /**
     * @return array{
     *     serialNumberHex:string,
     *     issuerNameDer:string,
     *     issuerPublicKeyDer:string
     * }
     */
    public function extract(
        string $certificatePem,
        string $issuerCertificatePem
    ): array {
        $certificate = openssl_x509_read($certificatePem);

        if ($certificate === false) {
            throw new RuntimeException(
                'Não foi possível ler certificado.'
            );
        }

        $certificateData = openssl_x509_parse($certificate);

        if ($certificateData === false) {
            throw new RuntimeException(
                'Não foi possível parsear certificado.'
            );
        }

        return [
            'serialNumberHex' => strtoupper(
                $certificateData['serialNumberHex']
            ),
            'issuerNameDer' => (new X509NameDerExtractor())
                ->extractSubjectNameDer($issuerCertificatePem),
            'issuerPublicKeyDer' => (new X509PublicKeyDerExtractor())
                ->extractSubjectPublicKeyDer($issuerCertificatePem),
        ];
    }
}
