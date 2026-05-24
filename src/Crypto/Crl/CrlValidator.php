<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Crl;

use NihilLabs\Pades\Crypto\X509\X509CertificateInfoExtractor;
use NihilLabs\Pades\Crypto\X509\X509CertificateIdentityExtractor;

final readonly class CrlValidator
{
    public function validate(
        string $crlDer,
        string $issuerCertificatePem,
        ?string $certificatePem = null,
        ?\DateTimeInterface $validationTime = null
    ): bool {
        try {
            $crl = (new CrlParser())->parse($crlDer);
            $issuer = (new X509CertificateIdentityExtractor())->extract($issuerCertificatePem);
        } catch (\RuntimeException) {
            return false;
        }

        $time = $validationTime ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        if ($crl->issuerNameDer !== $issuer->subjectNameDer) {
            return false;
        }

        if ($crl->authorityKeyIdentifier !== null && $issuer->subjectKeyIdentifier !== null && $crl->authorityKeyIdentifier !== $issuer->subjectKeyIdentifier) {
            return false;
        }

        if ($crl->nextUpdate !== null && $crl->nextUpdate->getTimestamp() < $time->getTimestamp()) {
            return false;
        }

        if (openssl_verify($crl->tbsCertListDer, $crl->signature, $issuerCertificatePem, $this->opensslAlgorithm($crl->signatureAlgorithmOid)) !== 1) {
            return false;
        }

        if ($certificatePem !== null) {
            $serial = (new X509CertificateInfoExtractor())->extract($certificatePem, $issuerCertificatePem)['serialNumberHex'];
            return ! $crl->isRevoked($serial);
        }

        return true;
    }

    private function opensslAlgorithm(string $oid): int
    {
        return match ($oid) {
            '1.2.840.113549.1.1.12' => OPENSSL_ALGO_SHA384,
            '1.2.840.113549.1.1.13' => OPENSSL_ALGO_SHA512,
            default => OPENSSL_ALGO_SHA256,
        };
    }
}
