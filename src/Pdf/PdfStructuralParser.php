<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfStructuralParser
{
    public function parse(string $pdfContent): PdfDocumentStructure
    {
        $objects = $this->parseObjects($pdfContent);
        $objects = $this->mergeObjectStreamObjects($objects);
        $xrefTables = [
            ...$this->parseXrefTables($pdfContent),
            ...$this->parseXrefStreams($objects),
        ];
        $trailers = [
            ...$this->parseTableTrailers($pdfContent),
            ...$this->parseXrefStreamTrailers($pdfContent, $objects),
        ];

        usort(
            $trailers,
            static fn (PdfTrailer $left, PdfTrailer $right): int => $left->offset <=> $right->offset
        );

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
     * @param array<int, PdfIndirectObject> $objects
     * @return array<int, PdfIndirectObject>
     */
    private function mergeObjectStreamObjects(array $objects): array
    {
        foreach ($objects as $object) {
            if (! str_contains($object->body, '/Type /ObjStm')) {
                continue;
            }

            foreach ($this->parseObjectStream($object) as $compressedObject) {
                $objects[$compressedObject->number] ??= $compressedObject;
            }
        }

        ksort($objects);

        return $objects;
    }

    /**
     * @return array<int, PdfIndirectObject>
     */
    private function parseObjectStream(PdfIndirectObject $objectStream): array
    {
        $stream = $this->decodedStream($objectStream);

        if ($stream === null) {
            return [];
        }

        $n = $this->dictionaryInteger($objectStream->body, 'N');
        $first = $this->dictionaryInteger($objectStream->body, 'First');

        if ($n === null || $first === null) {
            throw new RuntimeException('Object stream sem /N ou /First.');
        }

        $header = substr($stream, 0, $first);
        $objectsContent = substr($stream, $first);
        $parts = preg_split('/\s+/', trim($header));

        if ($parts === false || count($parts) < $n * 2) {
            throw new RuntimeException('Cabecalho de object stream invalido.');
        }

        $entries = [];

        for ($i = 0; $i < $n; $i++) {
            $entries[] = [
                'number' => (int) $parts[$i * 2],
                'offset' => (int) $parts[$i * 2 + 1],
            ];
        }

        $objects = [];

        foreach ($entries as $index => $entry) {
            $nextOffset = $entries[$index + 1]['offset'] ?? strlen($objectsContent);
            $body = substr(
                $objectsContent,
                $entry['offset'],
                $nextOffset - $entry['offset']
            );

            $objects[$entry['number']] = new PdfIndirectObject(
                number: $entry['number'],
                generation: 0,
                offset: $objectStream->offset,
                body: trim($body)
            );
        }

        return $objects;
    }

    /**
     * @return array<int, PdfTrailer>
     */
    private function parseTableTrailers(string $pdfContent): array
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
     * @param array<int, PdfIndirectObject> $objects
     * @return array<int, PdfTrailer>
     */
    private function parseXrefStreamTrailers(string $pdfContent, array $objects): array
    {
        if (! preg_match_all('/startxref\s*(\d+)\s*%%EOF/s', $pdfContent, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $trailers = [];

        foreach ($matches as $match) {
            $startXref = (int) $match[1][0];
            $xrefObject = $this->objectAtOffset($objects, $startXref);

            if ($xrefObject === null || ! str_contains($xrefObject->body, '/Type /XRef')) {
                continue;
            }

            $trailers[] = new PdfTrailer(
                offset: $xrefObject->offset,
                dictionary: $this->streamDictionary($xrefObject->body),
                startXref: $startXref
            );
        }

        return $trailers;
    }

    /**
     * @return array<int, array<int, array{offset:int,generation:int,in_use:bool,type?:int,object_stream?:int,index?:int}>>
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
                        'type' => $xrefEntry[3] === 'n' ? 1 : 0,
                    ];
                }
            }

            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * @param array<int, PdfIndirectObject> $objects
     * @return array<int, array<int, array{offset:int,generation:int,in_use:bool,type?:int,object_stream?:int,index?:int}>>
     */
    private function parseXrefStreams(array $objects): array
    {
        $tables = [];

        foreach ($objects as $object) {
            if (! str_contains($object->body, '/Type /XRef')) {
                continue;
            }

            $stream = $this->decodedStream($object);

            if ($stream === null) {
                continue;
            }

            $widths = $this->dictionaryIntegerArray($object->body, 'W');

            if (count($widths) !== 3) {
                throw new RuntimeException('XRef stream sem /W valido.');
            }

            $index = $this->dictionaryIntegerArray($object->body, 'Index');

            if ($index === []) {
                $size = $this->dictionaryInteger($object->body, 'Size') ?? 0;
                $index = [0, $size];
            }

            $entryLength = array_sum($widths);

            if ($entryLength <= 0) {
                throw new RuntimeException('XRef stream com entrada vazia.');
            }

            $cursor = 0;
            $table = [];

            for ($i = 0; $i < count($index); $i += 2) {
                $firstObject = $index[$i];
                $count = $index[$i + 1] ?? 0;

                for ($entry = 0; $entry < $count; $entry++) {
                    $chunk = substr($stream, $cursor, $entryLength);
                    $cursor += $entryLength;

                    if (strlen($chunk) !== $entryLength) {
                        throw new RuntimeException('XRef stream truncada.');
                    }

                    $fields = $this->readXrefStreamFields($chunk, $widths);
                    $type = $fields[0] === 0 ? 1 : $fields[0];
                    $objectNumber = $firstObject + $entry;

                    $table[$objectNumber] = match ($type) {
                        0 => [
                            'offset' => $fields[1],
                            'generation' => $fields[2],
                            'in_use' => false,
                            'type' => 0,
                        ],
                        1 => [
                            'offset' => $fields[1],
                            'generation' => $fields[2],
                            'in_use' => true,
                            'type' => 1,
                        ],
                        2 => [
                            'offset' => $objects[$fields[1]]->offset ?? 0,
                            'generation' => 0,
                            'in_use' => true,
                            'type' => 2,
                            'object_stream' => $fields[1],
                            'index' => $fields[2],
                        ],
                        default => throw new RuntimeException("Tipo xref stream invalido: {$type}."),
                    };
                }
            }

            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * @param array<int> $widths
     * @return array{0:int,1:int,2:int}
     */
    private function readXrefStreamFields(string $chunk, array $widths): array
    {
        $cursor = 0;
        $fields = [];

        foreach ($widths as $index => $width) {
            $raw = $width === 0 ? '' : substr($chunk, $cursor, $width);
            $cursor += $width;

            if ($width === 0 && $index === 0) {
                $fields[] = 1;
                continue;
            }

            $value = 0;

            for ($i = 0; $i < strlen($raw); $i++) {
                $value = ($value << 8) + ord($raw[$i]);
            }

            $fields[] = $value;
        }

        return [$fields[0] ?? 1, $fields[1] ?? 0, $fields[2] ?? 0];
    }

    private function decodedStream(PdfIndirectObject $object): ?string
    {
        $encoded = $this->streamBytes($object->body);

        if ($encoded === null) {
            return null;
        }

        if (! str_contains($this->streamDictionary($object->body), '/Filter')) {
            return $encoded;
        }

        if (! str_contains($this->streamDictionary($object->body), '/FlateDecode')) {
            throw new RuntimeException('Stream PDF com filtro nao suportado.');
        }

        $decoded = gzuncompress($encoded);

        if ($decoded === false) {
            throw new RuntimeException('Nao foi possivel descomprimir FlateDecode.');
        }

        return $decoded;
    }

    private function streamDictionary(string $body): string
    {
        if (! preg_match('/^\s*(<<.*?>>)\s*stream/s', $body, $matches)) {
            return $body;
        }

        return $matches[1];
    }

    private function streamBytes(string $body): ?string
    {
        if (! preg_match('/stream\r?\n(.*?)\r?\nendstream/s', $body, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function dictionaryInteger(string $dictionary, string $name): ?int
    {
        if (! preg_match('/\/' . preg_quote($name, '/') . '\s+(\d+)\b/', $dictionary, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array<int>
     */
    private function dictionaryIntegerArray(string $dictionary, string $name): array
    {
        if (! preg_match('/\/' . preg_quote($name, '/') . '\s*\[(.*?)\]/s', $dictionary, $matches)) {
            return [];
        }

        if (! preg_match_all('/\d+/', $matches[1], $numbers)) {
            return [];
        }

        return array_map('intval', $numbers[0]);
    }

    /**
     * @param array<int, PdfIndirectObject> $objects
     */
    private function objectAtOffset(array $objects, int $offset): ?PdfIndirectObject
    {
        foreach ($objects as $object) {
            if ($object->offset === $offset) {
                return $object;
            }
        }

        return null;
    }
}
