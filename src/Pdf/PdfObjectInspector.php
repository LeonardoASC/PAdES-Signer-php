<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfObjectInspector
{
    public function getHighestObjectNumber(string $pdfContent): int
    {
        return (new PdfStructuralParser())
            ->parse($pdfContent)
            ->highestObjectNumber();
    }

    public function getNextObjectNumber(string $pdfContent): int
    {
        return $this->getHighestObjectNumber($pdfContent) + 1;
    }
}
