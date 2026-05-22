<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use NihilLabs\Pades\Signing\SignatureCredentialInterface;

final readonly class OpenSslCertificateChainValidator implements CertificateChainValidatorInterface
{
    public function __construct(
        private ?RealCertificateChainCollector $chainCollector = null,
        private ?CertificateFormatNormalizer $normalizer = null
    ) {}

    public function validateCredential(
        SignatureCredentialInterface $credential,
        TrustStoreInterface $trustStore
    ): CertificateChainValidationResult {
        return $this->validateCertificateChain(
            signerCertificatePem: $credential->getCertificatePem(),
            candidateCertificatesPem: $credential->getCertificateChainPem(),
            trustStore: $trustStore
        );
    }

    /**
     * @param array<string> $candidateCertificatesPem
     */
    public function validateCertificateChain(
        string $signerCertificatePem,
        array $candidateCertificatesPem,
        TrustStoreInterface $trustStore
    ): CertificateChainValidationResult {
        $collector = $this->chainCollector ?? new RealCertificateChainCollector();
        $chainData = $collector->collect(
            signerCertificatePem: $signerCertificatePem,
            candidateCertificatesPem: $candidateCertificatesPem
        );
        $chain = $chainData['chain'];

        if ($chain === []) {
            return new CertificateChainValidationResult(
                trusted: false,
                chainPem: [],
                messages: ['Cadeia X.509 vazia.']
            );
        }

        foreach ($this->trustedCandidates($trustStore) as $trustAnchorPem) {
            $candidateChain = $this->chainWithTrustAnchor($chain, $trustAnchorPem);

            if ($candidateChain === null) {
                continue;
            }

            if (! $this->verifyLinks($candidateChain)) {
                continue;
            }

            return new CertificateChainValidationResult(
                trusted: true,
                chainPem: $candidateChain,
                trustAnchorPem: $trustAnchorPem
            );
        }

        return new CertificateChainValidationResult(
            trusted: false,
            chainPem: $chain,
            messages: ['Cadeia X.509 nao ancora em um certificado confiavel do trust store.']
        );
    }

    /**
     * @return array<string>
     */
    private function trustedCandidates(TrustStoreInterface $trustStore): array
    {
        return $trustStore->getTrustedCertificatesPem();
    }

    /**
     * @param array<string> $chain
     * @return ?array<string>
     */
    private function chainWithTrustAnchor(array $chain, string $trustAnchorPem): ?array
    {
        $normalizer = $this->normalizer ?? new CertificateFormatNormalizer();
        $trustFingerprint = hash('sha256', $normalizer->normalizeToDer($trustAnchorPem));

        foreach ($chain as $index => $certificatePem) {
            if (hash('sha256', $normalizer->normalizeToDer($certificatePem)) === $trustFingerprint) {
                return array_slice($chain, 0, $index + 1);
            }
        }

        $last = $chain[count($chain) - 1];

        if ($this->isIssuerOf($trustAnchorPem, $last)) {
            return [
                ...$chain,
                $trustAnchorPem,
            ];
        }

        return null;
    }

    /**
     * @param array<string> $chain
     */
    private function verifyLinks(array $chain): bool
    {
        for ($i = 0; $i < count($chain) - 1; $i++) {
            if ($this->verifyCertificateWithIssuer($chain[$i], $chain[$i + 1]) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function verifyCertificateWithIssuer(string $certificatePem, string $issuerPem): int
    {
        $certificate = openssl_x509_read($certificatePem);
        $issuer = openssl_x509_read($issuerPem);

        if ($certificate === false || $issuer === false) {
            return -1;
        }

        return openssl_x509_verify($certificate, $issuer);
    }

    private function isIssuerOf(string $issuerPem, string $certificatePem): bool
    {
        $extractor = new X509NameDerExtractor();

        return $extractor->extractSubjectNameDer($issuerPem)
            === $extractor->extractIssuerNameDer($certificatePem);
    }
}
