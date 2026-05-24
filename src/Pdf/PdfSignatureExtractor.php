<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureExtractor
{
    public function extractHexSignature(string $pdfContent): string
    {
        return (new EmbeddedPdfSignatureExtractor())
            ->extractFirst($pdfContent)
            ->contentsHex;
    }

    public function extractBinarySignature(string $pdfContent): string
    {
        return (new EmbeddedPdfSignatureExtractor())
            ->extractFirst($pdfContent)
            ->contentsDer;
    }

    public function extractBinarySignatureWithoutPadding(string $pdfContent): string
    {
        return (new EmbeddedPdfSignatureExtractor())
            ->extractFirst($pdfContent)
            ->contentsDerWithoutPadding;
    }
}
