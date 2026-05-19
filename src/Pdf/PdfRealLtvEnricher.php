<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Validation\RealLtvValidationMaterialFactory;
use RuntimeException;

final readonly class PdfRealLtvEnricher
{
    public function __construct(
        private ?RealLtvValidationMaterialFactory $materialFactory = null,
        private ?PdfLtvEnricher $enricher = null
    ) {}

    public function enrich(
        string $signedPdfContent,
        string $signerCertificatePem,
        array $candidateCertificatesPem = []
    ): string {
        $material = ($this->materialFactory ?? new RealLtvValidationMaterialFactory())
            ->create(
                signerCertificatePem: $signerCertificatePem,
                candidateCertificatesPem: $candidateCertificatesPem
            );

        return ($this->enricher ?? new PdfLtvEnricher())
            ->enrich(
                signedPdfContent: $signedPdfContent,
                material: $material
            );
    }

    public function enrichFile(
        string $inputPdf,
        string $outputPdf,
        string $signerCertificatePem,
        array $candidateCertificatesPem = []
    ): void {
        $pdf = file_get_contents($inputPdf);

        if ($pdf === false) {
            throw new InvalidArgumentException("Input PDF not found: {$inputPdf}");
        }

        $enriched = $this->enrich(
            signedPdfContent: $pdf,
            signerCertificatePem: $signerCertificatePem,
            candidateCertificatesPem: $candidateCertificatesPem
        );

        $success = file_put_contents($outputPdf, $enriched);

        if ($success === false) {
            throw new RuntimeException("Could not write enriched PDF: {$outputPdf}");
        }
    }
}
