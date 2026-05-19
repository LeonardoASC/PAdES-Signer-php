<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use RuntimeException;

final readonly class RealCertificateChainCollector
{
    public function __construct(
        private ?X509ExtensionExtractor $extensionExtractor = null,
        private ?AuthorityInfoAccessParser $aiaParser = null,
        private ?AiaCaIssuersDownloader $downloader = null,
        private ?CertificateFormatNormalizer $normalizer = null,
        private int $maxDepth = 8
    ) {}

    /**
     * @return array{
     *     signer:string,
     *     intermediates:array<string>,
     *     root:?string,
     *     chain:array<string>
     * }
     */
    /**
     * @param array<string> $candidateCertificatesPem
     * @return array{
     *     signer:string,
     *     intermediates:array<string>,
     *     root:?string,
     *     chain:array<string>
     * }
     */
    public function collect(
        string $signerCertificatePem,
        array $candidateCertificatesPem = []
    ): array {
        $normalizer = $this->normalizer ?? new CertificateFormatNormalizer();
        $chain = [
            $normalizer->normalizeToPem($signerCertificatePem),
        ];

        $seen = [
            hash('sha256', $normalizer->normalizeToDer($chain[0])) => true,
        ];

        $candidateCertificatesPem = $this->normalizeCandidateCertificates(
            candidateCertificatesPem: $candidateCertificatesPem,
            seen: $seen
        );

        $current = $chain[0];

        for ($depth = 0; $depth < $this->maxDepth; $depth++) {
            if ($this->isSelfSigned($current)) {
                break;
            }

            $issuer = $this->issuerFromCandidates(
                certificatePem: $current,
                candidatesPem: $candidateCertificatesPem,
                seen: $seen
            );

            if ($issuer === null) {
                $issuer = $this->downloadIssuer($current, $seen);
            }

            if ($issuer === null) {
                break;
            }

            $chain[] = $issuer;
            $current = $issuer;
        }

        $root = null;
        $intermediates = array_slice($chain, 1);

        if (count($chain) > 1 && $this->isSelfSigned($chain[count($chain) - 1])) {
            $root = array_pop($intermediates);
        }

        return [
            'signer' => $chain[0],
            'intermediates' => array_values($intermediates),
            'root' => $root,
            'chain' => $chain,
        ];
    }

    /**
     * @param array<string> $candidateCertificatesPem
     * @param array<string, bool> $seen
     * @return array<string>
     */
    private function normalizeCandidateCertificates(
        array $candidateCertificatesPem,
        array $seen
    ): array {
        $normalizer = $this->normalizer ?? new CertificateFormatNormalizer();
        $candidates = [];

        foreach ($candidateCertificatesPem as $candidateCertificatePem) {
            foreach ($normalizer->allToPem($candidateCertificatePem) as $certificatePem) {
                $fingerprint = hash('sha256', $normalizer->normalizeToDer($certificatePem));

                if (isset($seen[$fingerprint])) {
                    continue;
                }

                $seen[$fingerprint] = true;
                $candidates[] = $certificatePem;
            }
        }

        return $candidates;
    }

    /**
     * @param array<string> $candidatesPem
     * @param array<string, bool> $seen
     */
    private function issuerFromCandidates(
        string $certificatePem,
        array $candidatesPem,
        array &$seen
    ): ?string {
        $normalizer = $this->normalizer ?? new CertificateFormatNormalizer();

        foreach ($candidatesPem as $candidatePem) {
            if (! $this->isIssuerOf($candidatePem, $certificatePem)) {
                continue;
            }

            $fingerprint = hash('sha256', $normalizer->normalizeToDer($candidatePem));

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;

            return $candidatePem;
        }

        return null;
    }

    /**
     * @param array<string, bool> $seen
     */
    private function downloadIssuer(
        string $certificatePem,
        array &$seen
    ): ?string {
        $extensionExtractor = $this->extensionExtractor ?? new X509ExtensionExtractor();
        $aiaParser = $this->aiaParser ?? new AuthorityInfoAccessParser();
        $downloader = $this->downloader ?? new AiaCaIssuersDownloader();
        $normalizer = $this->normalizer ?? new CertificateFormatNormalizer();

        $aia = $extensionExtractor->authorityInfoAccess($certificatePem);

        if ($aia === null) {
            return null;
        }

        foreach ($aiaParser->caIssuersUrls($aia) as $url) {
            $downloaded = $downloader->download($url);

            if ($downloaded === null) {
                continue;
            }

            foreach ($normalizer->allToPem($downloaded) as $issuerPem) {
                try {
                    $issuerDer = $normalizer->normalizeToDer($issuerPem);
                } catch (RuntimeException) {
                    continue;
                }

                $fingerprint = hash('sha256', $issuerDer);

                if (isset($seen[$fingerprint])) {
                    continue;
                }

                if (! $this->isIssuerOf($issuerPem, $certificatePem)) {
                    continue;
                }

                $seen[$fingerprint] = true;

                return $issuerPem;
            }
        }

        return null;
    }

    private function isIssuerOf(
        string $issuerPem,
        string $certificatePem
    ): bool {
        try {
            $extractor = new X509NameDerExtractor();

            return $extractor->extractSubjectNameDer($issuerPem)
                === $extractor->extractIssuerNameDer($certificatePem);
        } catch (RuntimeException) {
            return false;
        }
    }

    private function isSelfSigned(string $certificatePem): bool
    {
        try {
            $extractor = new X509NameDerExtractor();

            return $extractor->extractSubjectNameDer($certificatePem)
                === $extractor->extractIssuerNameDer($certificatePem);
        } catch (RuntimeException) {
            return false;
        }
    }
}
