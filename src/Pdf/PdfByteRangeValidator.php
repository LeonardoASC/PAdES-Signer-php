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

        if ($start1 !== 0) {
            return false;
        }

        $contentsStart = $start1 + $length1;
        $contentsEnd = $start2;
        $contentsLength = $contentsEnd - $contentsStart;

        if ($contentsStart < 1 || $contentsLength <= 0) {
            return false;
        }

        if (
            ! isset($pdfContent[$contentsStart])
            || ! isset($pdfContent[$contentsEnd - 1])
            || $pdfContent[$contentsStart] !== '<'
            || $pdfContent[$contentsEnd - 1] !== '>'
        ) {
            return false;
        }

        $signedRevisionEnd = $start2 + $length2;

        return $signedRevisionEnd <= strlen($pdfContent);
    }
}
