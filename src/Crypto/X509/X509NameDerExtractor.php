<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use RuntimeException;

final readonly class X509NameDerExtractor
{
    public function extractIssuerNameDer(
        string $certificatePem
    ): string {
        $certificate = openssl_x509_read(
            $certificatePem
        );

        if ($certificate === false) {
            throw new RuntimeException(
                'Não foi possível ler certificado.'
            );
        }

        openssl_x509_export(
            $certificate,
            $exportedPem
        );

        $der = $this->pemToDer(
            $exportedPem
        );

        $position = strpos(
            $der,
            hex2bin('300D06092A864886F70D01010B0500')
        );

        if ($position === false) {
            return $der;
        }

        return substr(
            $der,
            0,
            $position
        );
    }

    private function pemToDer(
        string $pem
    ): string {
        $clean = preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s/',
            '',
            $pem
        );

        return base64_decode(
            $clean,
            true
        ) ?: '';
    }
}