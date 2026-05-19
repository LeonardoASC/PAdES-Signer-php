<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Pdf\Dss\PdfDssObjectBuilder;
use RuntimeException;

final readonly class PdfLtvEnricher
{
    public function enrich(
        string $signedPdfContent,
        LtvValidationMaterial $material
    ): string {
        if (! str_starts_with($signedPdfContent, '%PDF-')) {
            throw new InvalidArgumentException('Input content is not a PDF.');
        }

        if (! str_contains($signedPdfContent, '/ByteRange')) {
            throw new InvalidArgumentException('Input PDF does not contain a signature ByteRange.');
        }

        $objectInspector = new PdfObjectInspector();

        $dssObjects = (new PdfDssObjectBuilder())
            ->build(
                firstObjectNumber: $objectInspector->getNextObjectNumber($signedPdfContent),
                material: $material,
                vriHash: $this->signatureVriHash($signedPdfContent)
            );

        $catalogInspector = new PdfCatalogInspector();

        $catalogNumber = $catalogInspector
            ->getCatalogObjectNumber($signedPdfContent);

        $catalogBody = $catalogInspector
            ->getCatalogObjectBody($signedPdfContent);

        $updatedCatalog = (new PdfCatalogUpdater())
            ->addDss(
                catalogBody: $catalogBody,
                dssObjectNumber: $dssObjects['dssObjectNumber']
            );

        $objects = $dssObjects['objects'];
        $objects[$catalogNumber] = $updatedCatalog;

        return (new IncrementalPdfWriter())
            ->appendObjects(
                pdfContent: $signedPdfContent,
                objects: $objects
            );
    }

    public function enrichFile(
        string $inputPdf,
        string $outputPdf,
        LtvValidationMaterial $material
    ): void {
        $pdf = file_get_contents($inputPdf);

        if ($pdf === false) {
            throw new InvalidArgumentException("Input PDF not found: {$inputPdf}");
        }

        $enriched = $this->enrich(
            signedPdfContent: $pdf,
            material: $material
        );

        $success = file_put_contents($outputPdf, $enriched);

        if ($success === false) {
            throw new RuntimeException("Could not write enriched PDF: {$outputPdf}");
        }
    }

    private function signatureVriHash(string $signedPdfContent): string
    {
        $signature = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($signedPdfContent);

        return strtoupper(hash('sha1', $signature));
    }
}
