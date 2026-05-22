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

        $structure = (new PdfStructuralParser())->parse($pdfContent);

        if ($structure->xrefTables !== []) {
            $structure = (new PdfStructureValidator())->validate($pdfContent);
        }
        $latestTrailer = $structure->latestTrailer();
        $originalLength = strlen($pdfContent);
        $rootReference = $this->getRootReference($pdfContent);
        $infoReference = $latestTrailer->getReference('Info');
        $documentId = $latestTrailer->firstDocumentIdHex();

        $body = '';
        $offsets = [];

        foreach ($objects as $objectNumber => $objectBody) {
            $body .= "\n";
            $offsets[$objectNumber] = $originalLength + strlen($body);

            $body .= "{$objectNumber} 0 obj\n"
                . $objectBody . "\n"
                . "endobj\n";
        }

        $xrefOffset = $originalLength + strlen($body);
        $xref = $this->buildXref($offsets);
        $highestExistingObject = $structure->objects === []
            ? 0
            : $structure->highestObjectNumber();
        $size = max(max(array_keys($objects)), $highestExistingObject) + 1;

        $trailer = "trailer\n"
            . "<<\n"
            . "/Size {$size}\n"
            . "/Root {$rootReference}\n";

        if ($infoReference !== null) {
            $trailer .= "/Info {$infoReference}\n";
        }

        if ($documentId !== null) {
            $trailer .= "/ID [ <{$documentId}> <" . bin2hex(random_bytes(16)) . "> ]\n";
        }

        $trailer .= "/Prev {$latestTrailer->startXref}\n"
            . ">>\n"
            . "startxref\n"
            . $xrefOffset . "\n"
            . "%%EOF\n";

        $updated = $pdfContent . $body . $xref . $trailer;

        (new PdfIncrementalUpdateValidator())
            ->validateAppendOnly($pdfContent, $updated);

        return $updated;
    }

    /**
     * @param array<int, int> $offsets
     */
    private function buildXref(array $offsets): string
    {
        ksort($offsets);

        $xref = "xref\n"
            . "0 1\n"
            . "0000000000 65535 f \n";
        $sectionStart = null;
        $sectionOffsets = [];
        $previousObjectNumber = null;

        foreach ($offsets as $objectNumber => $offset) {
            if (
                $sectionStart !== null
                && $previousObjectNumber !== null
                && $objectNumber !== $previousObjectNumber + 1
            ) {
                $xref .= $this->formatXrefSection($sectionStart, $sectionOffsets);
                $sectionStart = null;
                $sectionOffsets = [];
            }

            if ($sectionStart === null) {
                $sectionStart = $objectNumber;
            }

            $sectionOffsets[] = $offset;
            $previousObjectNumber = $objectNumber;
        }

        if ($sectionStart !== null) {
            $xref .= $this->formatXrefSection($sectionStart, $sectionOffsets);
        }

        return $xref;
    }

    /**
     * @param array<int> $offsets
     */
    private function formatXrefSection(
        int $firstObject,
        array $offsets
    ): string {
        $xref = "{$firstObject} " . count($offsets) . "\n";

        foreach ($offsets as $offset) {
            $xref .= sprintf("%010d 00000 n \n", $offset);
        }

        return $xref;
    }

    public function getLastStartXref(string $pdfContent): int
    {
        $structure = (new PdfStructuralParser())->parse($pdfContent);

        if ($structure->trailers !== []) {
            return $structure->latestTrailer()->startXref;
        }

        if (! preg_match_all('/startxref\s+(\d+)/', $pdfContent, $matches)) {
            throw new RuntimeException('startxref nao encontrado no PDF.');
        }

        return (int) end($matches[1]);
    }

    public function getRootReference(string $pdfContent): string
    {
        $root = (new PdfStructuralParser())
            ->parse($pdfContent)
            ->latestTrailer()
            ->getReference('Root');

        if ($root === null) {
            throw new RuntimeException('/Root nao encontrado no trailer do PDF.');
        }

        return $root;
    }
}
