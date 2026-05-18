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

    /**
     * @param array<int, string> $objects
     */
    public function appendObjects(
        string $pdfContent,
        array $objects
    ): string {
        if ($objects === []) {
            throw new RuntimeException('Nenhum objeto informado para incremental update.');
        }

        $originalLength = strlen($pdfContent);
        $previousStartXref = $this->getLastStartXref($pdfContent);

        $body = '';
        $offsets = [];

        foreach ($objects as $objectNumber => $objectBody) {
            $offsets[$objectNumber] = $originalLength + strlen($body);

            $body .= "\n"
                . "{$objectNumber} 0 obj\n"
                . $objectBody . "\n"
                . "endobj\n";
        }

        $xrefOffset = $originalLength + strlen($body);

        $xref = "xref\n";

        foreach ($offsets as $objectNumber => $offset) {
            $xref .= "{$objectNumber} 1\n";
            $xref .= sprintf("%010d 00000 n \n", $offset);
        }

        $size = max(array_keys($objects)) + 1;

        $trailer = "trailer\n"
            . "<<\n"
            . "/Size {$size}\n"
            . "/Prev {$previousStartXref}\n"
            . ">>\n"
            . "startxref\n"
            . $xrefOffset . "\n"
            . "%%EOF\n";

        return $pdfContent . $body . $xref . $trailer;
    }

    public function getLastStartXref(string $pdfContent): int
    {
        if (! preg_match_all('/startxref\s+(\d+)/', $pdfContent, $matches)) {
            throw new RuntimeException('startxref não encontrado no PDF.');
        }

        return (int) end($matches[1]);
    }
}
