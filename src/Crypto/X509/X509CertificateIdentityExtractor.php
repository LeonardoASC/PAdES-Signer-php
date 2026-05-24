<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class X509CertificateIdentityExtractor
{
    public function extract(string $certificatePem): X509CertificateIdentity
    {
        $normalizer = new CertificateFormatNormalizer();
        $nameExtractor = new X509NameDerExtractor();
        $pem = $normalizer->normalizeToPem($certificatePem);
        $subjectNameDer = $nameExtractor->extractSubjectNameDer($pem);
        $issuerNameDer = $nameExtractor->extractIssuerNameDer($pem);
        $extensions = (new X509ExtensionExtractor())->extract($pem);

        return new X509CertificateIdentity(
            pem: $pem,
            fingerprint: hash('sha256', $normalizer->normalizeToDer($pem)),
            subjectNameDer: $subjectNameDer,
            issuerNameDer: $issuerNameDer,
            subjectKeyIdentifier: $this->normalizedKeyIdentifier($extensions['subjectKeyIdentifier'] ?? null),
            authorityKeyIdentifier: $this->authorityKeyIdentifier($extensions['authorityKeyIdentifier'] ?? null),
            selfIssued: $subjectNameDer === $issuerNameDer
        );
    }

    private function authorityKeyIdentifier(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        if (preg_match('/keyid:([0-9A-Fa-f:\s]+)/', $value, $matches) === 1) {
            return $this->normalizedKeyIdentifier($matches[1]);
        }

        return $this->normalizedKeyIdentifier($value);
    }

    private function normalizedKeyIdentifier(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $value));

        return $normalized === '' ? null : $normalized;
    }
}
