<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfByteRangeValidator
{
    public function validate(string $pdfContent): bool
    {
        if (! preg_match(
            '/\/ByteRange\s*\[(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\]/',
            $pdfContent,
            $matches
        )) {
            return false;
        }

        $start1 = (int) $matches[1];
        $length1 = (int) $matches[2];
        $start2 = (int) $matches[3];
        $length2 = (int) $matches[4];

        $contentsLength = $start2 - ($start1 + $length1);

        if ($contentsLength <= 0) {
            return false;
        }

        $totalCovered = $length1 + $length2;

        $expectedTotal = strlen($pdfContent) - $contentsLength;

        return $totalCovered === $expectedTotal;
    }
}