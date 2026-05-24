<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\X509\CertificateFormatNormalizer;
use NihilLabs\Pades\Crypto\X509\X509CertificateInfoExtractor;
use NihilLabs\Pades\Crypto\X509\X509PublicKeyDerExtractor;

final readonly class OcspResponseValidator
{
    public function validate(
        string $responseDer,
        string $certificatePem,
        string $issuerCertificatePem,
        ?string $expectedNonce = null,
        ?\DateTimeInterface $validationTime = null
    ): OcspValidationResult {
        try {
            $parsed = (new OcspResponseParser())->parse($responseDer);
        } catch (\RuntimeException) {
            return new OcspValidationResult(false, null);
        }

        if ($parsed->responseStatus !== 0 || $parsed->responses === []) {
            return new OcspValidationResult(false, null);
        }

        $single = $parsed->responses[0];
        $valid = $this->certIdMatches($single, $certificatePem, $issuerCertificatePem)
            && $this->timesValid($single, $parsed, $validationTime ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            && ($expectedNonce === null || hash_equals($expectedNonce, (string) $parsed->nonce))
            && $this->signatureValid($parsed, $issuerCertificatePem)
            && $this->responderAuthorized($parsed, $issuerCertificatePem);

        return new OcspValidationResult($valid, $single->certificateStatus);
    }

    private function certIdMatches(OcspSingleResponse $single, string $certificatePem, string $issuerCertificatePem): bool
    {
        $info = (new X509CertificateInfoExtractor())->extract($certificatePem, $issuerCertificatePem);
        $issuerKey = (new X509PublicKeyDerExtractor())->extractSubjectPublicKeyBitStringValue($issuerCertificatePem);

        return $single->hashAlgorithmOid === '1.3.14.3.2.26'
            && hash_equals($single->issuerNameHash, sha1($info['issuerNameDer'], true))
            && hash_equals($single->issuerKeyHash, sha1($issuerKey, true))
            && strtoupper(ltrim($info['serialNumberHex'], '0')) === strtoupper(ltrim($single->serialNumberHex, '0'));
    }

    private function timesValid(OcspSingleResponse $single, ParsedOcspResponse $parsed, \DateTimeInterface $time): bool
    {
        $timestamp = $time->getTimestamp();

        if ($parsed->producedAt !== null && $parsed->producedAt->getTimestamp() > $timestamp) {
            return false;
        }

        if ($single->thisUpdate->getTimestamp() > $timestamp) {
            return false;
        }

        return $single->nextUpdate === null || $single->nextUpdate->getTimestamp() >= $timestamp;
    }

    private function signatureValid(ParsedOcspResponse $parsed, string $issuerCertificatePem): bool
    {
        if ($parsed->tbsResponseDataDer === null || $parsed->signature === null || $parsed->signatureAlgorithmOid === null) {
            return false;
        }

        return openssl_verify(
            $parsed->tbsResponseDataDer,
            $parsed->signature,
            $issuerCertificatePem,
            $this->opensslAlgorithm($parsed->signatureAlgorithmOid)
        ) === 1;
    }

    private function responderAuthorized(ParsedOcspResponse $parsed, string $issuerCertificatePem): bool
    {
        if ($parsed->certificatesDer === []) {
            return true;
        }

        $normalizer = new CertificateFormatNormalizer();
        $issuerDer = $normalizer->normalizeToDer($issuerCertificatePem);

        foreach ($parsed->certificatesDer as $certificateDer) {
            if (hash_equals($issuerDer, $normalizer->normalizeToDer($certificateDer))) {
                return true;
            }

            $certificatePem = $normalizer->toPem($certificateDer);

            if (openssl_x509_verify($certificatePem, $issuerCertificatePem) !== 1) {
                continue;
            }

            $extensions = openssl_x509_parse($certificatePem)['extensions'] ?? [];
            $eku = strtolower((string) ($extensions['extendedKeyUsage'] ?? ''));

            if (str_contains($eku, 'ocsp') || str_contains($eku, '1.3.6.1.5.5.7.3.9')) {
                return true;
            }
        }

        return false;
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
