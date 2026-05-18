<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

final class PdfSigner
{
    public function sign(string $inputPdf, string $outputPdf): void
    {
        if (! file_exists($inputPdf)) {
            throw new \InvalidArgumentException("PDF não encontrado: {$inputPdf}");
        }

        if (strtolower(pathinfo($inputPdf, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new \InvalidArgumentException("O arquivo de entrada precisa ser um PDF.");
        }

        copy($inputPdf, $outputPdf);
    }
}