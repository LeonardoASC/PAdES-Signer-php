<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfAcroFormInspector
{
    public function getAcroFormObjectNumber(string $catalogBody): ?int
    {
        if (! preg_match('/\/AcroForm\s+(\d+)\s+\d+\s+R\b/', $catalogBody, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
