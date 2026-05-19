<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use RuntimeException;

final readonly class CertificateFormatNormalizer
{
    public function isPem(string $certificate): bool
    {
        return str_contains($certificate, '-----BEGIN CERTIFICATE-----');
    }

    public function toDer(string $certificate): string
    {
        if (! $this->isPem($certificate)) {
            return $certificate;
        }

        if (! preg_match('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s', $certificate, $matches)) {
            throw new RuntimeException('PEM do certificado invalido.');
        }

        $clean = preg_replace('/\s+/', '', $matches[1]);

        if ($clean === null || $clean === '') {
            throw new RuntimeException('PEM do certificado vazio.');
        }

        $der = base64_decode($clean, true);

        if ($der === false) {
            throw new RuntimeException('Nao foi possivel converter certificado PEM para DER.');
        }

        return $der;
    }

    public function toPem(string $certificate): string
    {
        if ($this->isPem($certificate)) {
            return $this->firstPemCertificate($certificate);
        }

        return "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($certificate), 64, "\n")
            . "-----END CERTIFICATE-----\n";
    }

    public function normalizeToPem(string $certificate): string
    {
        $pem = $this->toPem($certificate);

        if (@openssl_x509_read($pem) === false) {
            throw new RuntimeException('Certificado X.509 invalido.');
        }

        return $pem;
    }

    public function normalizeToDer(string $certificate): string
    {
        $der = $this->toDer($certificate);
        $pem = $this->toPem($der);

        if (@openssl_x509_read($pem) === false) {
            throw new RuntimeException('Certificado X.509 invalido.');
        }

        return $der;
    }

    /**
     * @return array<string>
     */
    public function allToPem(string $certificateMaterial): array
    {
        $certificates = [];

        foreach ($this->pemCertificates($certificateMaterial) as $certificatePem) {
            $certificates[] = $this->normalizeToPem($certificatePem);
        }

        if ($certificates !== []) {
            return $this->uniquePemCertificates($certificates);
        }

        try {
            return [$this->normalizeToPem($certificateMaterial)];
        } catch (RuntimeException) {
        }

        return $this->pkcs7CertificatesToPem($certificateMaterial);
    }

    private function firstPemCertificate(string $certificate): string
    {
        if (! preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $certificate, $matches)) {
            throw new RuntimeException('PEM do certificado invalido.');
        }

        return trim($matches[0]) . "\n";
    }

    /**
     * @return array<string>
     */
    private function pemCertificates(string $certificateMaterial): array
    {
        if (! preg_match_all(
            '/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s',
            $certificateMaterial,
            $matches
        )) {
            return [];
        }

        return array_map(
            static fn (string $certificatePem): string => trim($certificatePem) . "\n",
            $matches[0]
        );
    }

    /**
     * @return array<string>
     */
    private function pkcs7CertificatesToPem(string $certificateMaterial): array
    {
        $pkcs7 = $certificateMaterial;

        if (! str_contains($pkcs7, '-----BEGIN PKCS7-----')) {
            $pkcs7 = "-----BEGIN PKCS7-----\n"
                . chunk_split(base64_encode($certificateMaterial), 64, "\n")
                . "-----END PKCS7-----\n";
        }

        $certificates = [];

        if (! openssl_pkcs7_read($pkcs7, $certificates)) {
            return [];
        }

        return $this->uniquePemCertificates(array_map(
            fn (string $certificatePem): string => $this->normalizeToPem($certificatePem),
            $certificates
        ));
    }

    /**
     * @param array<string> $certificatesPem
     * @return array<string>
     */
    private function uniquePemCertificates(array $certificatesPem): array
    {
        $unique = [];
        $seen = [];

        foreach ($certificatesPem as $certificatePem) {
            $fingerprint = hash('sha256', $this->normalizeToDer($certificatePem));

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $unique[] = $certificatePem;
        }

        return $unique;
    }
}
