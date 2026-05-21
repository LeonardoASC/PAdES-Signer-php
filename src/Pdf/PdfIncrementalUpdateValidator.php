<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfIncrementalUpdateValidator
{
    public function validateAppendOnly(
        string $originalPdf,
        string $updatedPdf
    ): void {
        if (! str_starts_with($updatedPdf, $originalPdf)) {
            throw new RuntimeException('Incremental update alterou bytes existentes do PDF.');
        }

        $previousStartXref = (new IncrementalPdfWriter())
            ->getLastStartXref($originalPdf);

        if (! str_contains($updatedPdf, "/Prev {$previousStartXref}")) {
            throw new RuntimeException('Incremental update nao preserva /Prev da revisao anterior.');
        }

        if (! preg_match_all('/startxref\s+(\d+)\s*%%EOF/s', $updatedPdf, $matches)) {
            throw new RuntimeException('Incremental update sem startxref final.');
        }

        $lastStartXref = (int) end($matches[1]);

        if (substr($updatedPdf, $lastStartXref, 4) !== 'xref') {
            throw new RuntimeException('startxref final nao aponta para xref table.');
        }
    }
}
