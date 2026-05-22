<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\X509\CertificateFormatNormalizer;
use NihilLabs\Pades\Crypto\X509\RevocationProviderInterface;

final readonly class PdfAutomaticLtvEnricher
{
    public function __construct(
        private PdfLtvEnricher $enricher = new PdfLtvEnricher(),
        private CertificateFormatNormalizer $normalizer = new CertificateFormatNormalizer()
    ) {}

    /**
     * @param array<string> $certificateChainPem
     */
    public function enrich(
        string $signedPdfContent,
        array $certificateChainPem,
        RevocationProviderInterface $revocationProvider
    ): string {
        $revocation = $revocationProvider->collect($certificateChainPem);
        $certificatesDer = [];

        foreach ($certificateChainPem as $certificatePem) {
            $certificatesDer[] = $this->normalizer->normalizeToDer($certificatePem);
        }

        return $this->enricher->enrich(
            signedPdfContent: $signedPdfContent,
            material: new LtvValidationMaterial(
                certificatesDer: $this->unique($certificatesDer),
                ocspResponsesDer: $this->unique($revocation->ocspResponsesDer),
                crlsDer: $this->unique($revocation->crlsDer)
            )
        );
    }

    /**
     * @param array<string> $objects
     * @return array<string>
     */
    private function unique(array $objects): array
    {
        $unique = [];
        $seen = [];

        foreach ($objects as $object) {
            $hash = hash('sha256', $object);

            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $unique[] = $object;
        }

        return $unique;
    }
}
