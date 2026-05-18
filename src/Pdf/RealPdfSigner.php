<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;

final readonly class RealPdfSigner
{
    public function sign(
        string $inputPdf,
        string $outputPdf
    ): void {
        if (! file_exists($inputPdf)) {
            throw new InvalidArgumentException("PDF de entrada não encontrado: {$inputPdf}");
        }

        $content = file_get_contents($inputPdf);

        if ($content === false || ! str_starts_with($content, '%PDF-')) {
            throw new InvalidArgumentException('Arquivo de entrada não é um PDF válido.');
        }

        copy($inputPdf, $outputPdf);
    }
}