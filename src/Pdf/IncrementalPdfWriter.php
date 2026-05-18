<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class IncrementalPdfWriter
{
    public function appendObject(
        string $pdfContent,
        int $objectNumber,
        string $objectBody
    ): string {
        $originalLength = strlen($pdfContent);
        $previousStartXref = $this->getLastStartXref($pdfContent);

        $object = "\n"
            . "{$objectNumber} 0 obj\n"
            . $objectBody . "\n"
            . "endobj\n";

        $objectOffset = $originalLength;
        $xrefOffset = $objectOffset + strlen($object);

        $xref = "xref\n"
            . "{$objectNumber} 1\n"
            . sprintf("%010d 00000 n \n", $objectOffset);

        $trailer = "trailer\n"
            . "<<\n"
            . "/Size " . ($objectNumber + 1) . "\n"
            . "/Prev " . $previousStartXref . "\n"
            . ">>\n"
            . "startxref\n"
            . $xrefOffset . "\n"
            . "%%EOF\n";

        return $pdfContent . $object . $xref . $trailer;
    }

    public function getLastStartXref(string $pdfContent): int
    {
        if (! preg_match_all('/startxref\s+(\d+)/', $pdfContent, $matches)) {
            throw new RuntimeException('startxref não encontrado no PDF.');
        }

        return (int) end($matches[1]);
    }
}
