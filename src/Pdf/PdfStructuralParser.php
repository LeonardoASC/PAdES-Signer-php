<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfStructuralParser
{
    public function parse(string $pdfContent): PdfDocumentStructure
    {
        $objects = $this->parseObjects($pdfContent);
        $trailers = $this->parseTrailers($pdfContent);
        $xrefTables = $this->parseXrefTables($pdfContent);

        return new PdfDocumentStructure(
            content: $pdfContent,
            objects: $objects,
            xrefTables: $xrefTables,
            trailers: $trailers
        );
    }

    /**
     * @return array<int, PdfIndirectObject>
     */
    private function parseObjects(string $pdfContent): array
    {
        if (! preg_match_all('/(?m)(\d+)\s+(\d+)\s+obj\b(.*?)\bendobj\b/s', $pdfContent, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $objects = [];

        foreach ($matches as $match) {
            $number = (int) $match[1][0];
            $generation = (int) $match[2][0];
            $offset = $match[0][1];
            $body = trim($match[3][0]);

            $objects[$number] = new PdfIndirectObject(
                number: $number,
                generation: $generation,
                offset: $offset,
                body: $body
            );
        }

        ksort($objects);

        return $objects;
    }

    /**
     * @return array<int, PdfTrailer>
     */
    private function parseTrailers(string $pdfContent): array
    {
        if (! preg_match_all('/trailer\s*(<<.*?>>)\s*startxref\s*(\d+)\s*%%EOF/s', $pdfContent, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $trailers = [];

        foreach ($matches as $match) {
            $trailers[] = new PdfTrailer(
                offset: $match[0][1],
                dictionary: $match[1][0],
                startXref: (int) $match[2][0]
            );
        }

        return $trailers;
    }

    /**
     * @return array<int, array<int, array{offset:int,generation:int,in_use:bool}>>
     */
    private function parseXrefTables(string $pdfContent): array
    {
        $tables = [];

        if (! preg_match_all('/(?m)^xref\s*(.*?)(?=trailer\s*<<)/s', $pdfContent, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        foreach ($matches as $match) {
            $xrefBody = (new PdfLineEndingNormalizer())
                ->normalizeStructuralSegment($match[1][0]);
            $lines = preg_split('/\n/', trim($xrefBody));

            if ($lines === false) {
                throw new RuntimeException('Nao foi possivel ler xref table.');
            }

            $table = [];

            for ($i = 0; $i < count($lines); $i++) {
                $line = trim($lines[$i]);

                if ($line === '') {
                    continue;
                }

                if (! preg_match('/^(\d+)\s+(\d+)$/', $line, $section)) {
                    throw new RuntimeException("Secao xref invalida: {$line}");
                }

                $firstObject = (int) $section[1];
                $count = (int) $section[2];

                for ($entry = 0; $entry < $count; $entry++) {
                    $i++;
                    $entryLine = $lines[$i] ?? null;

                    if ($entryLine === null || ! preg_match('/^(\d{10})\s+(\d{5})\s+([nf])\s*$/', trim($entryLine), $xrefEntry)) {
                        throw new RuntimeException('Entrada xref invalida.');
                    }

                    $table[$firstObject + $entry] = [
                        'offset' => (int) $xrefEntry[1],
                        'generation' => (int) $xrefEntry[2],
                        'in_use' => $xrefEntry[3] === 'n',
                    ];
                }
            }

            $tables[] = $table;
        }

        return $tables;
    }

}
