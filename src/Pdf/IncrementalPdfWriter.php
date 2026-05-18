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
        return $this->appendObjects(
            pdfContent: $pdfContent,
            objects: [
                $objectNumber => $objectBody,
            ]
        );
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
        $rootReference = $this->getRootReference($pdfContent);

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

        ksort($offsets);

        $firstObject = min(array_keys($offsets));
        $countObjects = count($offsets);

        $xref = "xref\n";
        $xref .= "{$firstObject} {$countObjects}\n";

        foreach ($offsets as $offset) {
            $xref .= sprintf("%010d 00000 n \n", $offset);
        }

        $size = max(array_keys($objects)) + 1;

        $trailer = "trailer\n"
            . "<<\n"
            . "/Size {$size}\n"
            . "/Root {$rootReference}\n"
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

    public function getRootReference(string $pdfContent): string
    {
        if (! preg_match('/\/Root\s+(\d+\s+\d+\s+R)/', $pdfContent, $matches)) {
            throw new RuntimeException('/Root não encontrado no trailer do PDF.');
        }

        return $matches[1];
    }
}
