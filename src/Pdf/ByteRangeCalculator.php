<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class ByteRangeCalculator
{
    public function calculate(
        string $pdfContent,
        int $contentsStart,
        int $contentsEnd
    ): ByteRange {
        $pdfLength = strlen($pdfContent);

        if ($contentsStart < 0 || $contentsEnd <= $contentsStart || $contentsEnd > $pdfLength) {
            throw new RuntimeException('Intervalo /Contents inválido para cálculo do ByteRange.');
        }

        return new ByteRange(
            start1: 0,
            length1: $contentsStart,
            start2: $contentsEnd,
            length2: $pdfLength - $contentsEnd
        );
    }

    public function extractSignedData(
        string $pdfContent,
        ByteRange $byteRange
    ): string {
        return substr($pdfContent, $byteRange->start1, $byteRange->length1)
            . substr($pdfContent, $byteRange->start2, $byteRange->length2);
    }
}