<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use RuntimeException;

final readonly class CertificatePathBuilder
{
    public function __construct(
        private ?X509CertificateIdentityExtractor $identityExtractor = null,
        private int $maxDepth = 8
    ) {}

    /**
     * @param array<string> $candidatesPem
     * @param array<string> $trustAnchorsPem
     * @return list<array<string>>
     */
    public function buildPaths(
        string $signerCertificatePem,
        array $candidatesPem,
        array $trustAnchorsPem
    ): array {
        $extractor = $this->identityExtractor ?? new X509CertificateIdentityExtractor();
        $signer = $extractor->extract($signerCertificatePem);
        $candidates = $this->identities([...$candidatesPem, ...$trustAnchorsPem]);
        $trustAnchors = $this->identities($trustAnchorsPem);

        return $this->walk(
            current: $signer,
            candidates: $candidates,
            trustAnchors: $trustAnchors,
            path: [$signer->pem],
            seen: [$signer->fingerprint => true],
            depth: 0
        );
    }

    /**
     * @param array<string> $certificatesPem
     * @return list<X509CertificateIdentity>
     */
    private function identities(array $certificatesPem): array
    {
        $extractor = $this->identityExtractor ?? new X509CertificateIdentityExtractor();
        $identities = [];
        $seen = [];

        foreach ($certificatesPem as $certificatePem) {
            try {
                $identity = $extractor->extract($certificatePem);
            } catch (RuntimeException) {
                continue;
            }

            if (isset($seen[$identity->fingerprint])) {
                continue;
            }

            $seen[$identity->fingerprint] = true;
            $identities[] = $identity;
        }

        return $identities;
    }

    /**
     * @param list<X509CertificateIdentity> $candidates
     * @param list<X509CertificateIdentity> $trustAnchors
     * @param array<string> $path
     * @param array<string, true> $seen
     * @return list<array<string>>
     */
    private function walk(
        X509CertificateIdentity $current,
        array $candidates,
        array $trustAnchors,
        array $path,
        array $seen,
        int $depth
    ): array {
        if ($depth >= $this->maxDepth) {
            return [];
        }

        $paths = [];

        foreach ($trustAnchors as $trustAnchor) {
            if ($trustAnchor->fingerprint === $current->fingerprint) {
                $paths[] = $path;
            }
        }

        foreach ($candidates as $candidate) {
            if (isset($seen[$candidate->fingerprint]) || ! $this->isIssuerCandidate($candidate, $current)) {
                continue;
            }

            $paths = [
                ...$paths,
                ...$this->walk(
                    current: $candidate,
                    candidates: $candidates,
                    trustAnchors: $trustAnchors,
                    path: [...$path, $candidate->pem],
                    seen: [...$seen, $candidate->fingerprint => true],
                    depth: $depth + 1
                ),
            ];
        }

        return $paths;
    }

    private function isIssuerCandidate(X509CertificateIdentity $issuer, X509CertificateIdentity $certificate): bool
    {
        if ($issuer->subjectNameDer !== $certificate->issuerNameDer) {
            return false;
        }

        if ($certificate->authorityKeyIdentifier !== null && $issuer->subjectKeyIdentifier !== null) {
            return $certificate->authorityKeyIdentifier === $issuer->subjectKeyIdentifier;
        }

        return true;
    }
}
