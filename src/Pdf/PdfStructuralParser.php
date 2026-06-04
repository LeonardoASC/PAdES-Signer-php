<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfStructuralParser
{
    private const int MAX_DECODED_STREAM_BYTES = 104857600;

    public function parse(string $pdfContent): PdfDocumentStructure
    {
        $objects = $this->parseObjects($pdfContent);
        $objects = $this->mergeObjectStreamObjects($objects);
        $tableTrailers = $this->parseTableTrailers($pdfContent);
        $xrefTables = [
            ...$this->parseXrefTables($pdfContent),
            ...$this->parseXrefStreams($objects),
        ];
        $trailers = [
            ...$tableTrailers,
            ...$this->parseXrefStreamTrailers($pdfContent, $objects),
            ...$this->parseHybridXrefStreamTrailers($tableTrailers, $objects),
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
        return (new PdfIndirectObjectScanner())->scan($pdfContent);
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
     * @param array<int, PdfTrailer> $tableTrailers
     * @param array<int, PdfIndirectObject> $objects
     * @return array<int, PdfTrailer>
     */
    private function parseHybridXrefStreamTrailers(array $tableTrailers, array $objects): array
    {
        $trailers = [];

        foreach ($tableTrailers as $tableTrailer) {
            $xrefStreamOffset = $tableTrailer->getInteger('XRefStm');

            if ($xrefStreamOffset === null) {
                continue;
            }

            $xrefObject = $this->objectAtOffset($objects, $xrefStreamOffset);

            if ($xrefObject === null || ! (new PdfDictionaryReader())->hasNameValue($this->streamDictionary($xrefObject->body), 'Type', 'XRef')) {
                throw new RuntimeException('/XRefStm nao aponta para xref stream valido.');
            }

            $trailers[] = new PdfTrailer(
                offset: $xrefObject->offset,
                dictionary: $this->streamDictionary($xrefObject->body),
                startXref: $xrefObject->offset
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
            if (! (new PdfDictionaryReader())->hasNameValue($this->streamDictionary($object->body), 'Type', 'XRef')) {
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

            $stream = $this->decodeXrefStreamPredictor(
                stream: $stream,
                dictionary: $this->streamDictionary($object->body),
                entryLength: $entryLength
            );

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

    private function decodeXrefStreamPredictor(
        string $stream,
        string $dictionary,
        int $entryLength
    ): string {
        $decodeParms = (new PdfDictionaryReader())->getValue($dictionary, 'DecodeParms');

        if ($decodeParms === null) {
            return $stream;
        }

        $predictor = (new PdfDictionaryReader())->getInteger($decodeParms, 'Predictor') ?? 1;

        if ($predictor === 1) {
            return $stream;
        }

        $columns = (new PdfDictionaryReader())->getInteger($decodeParms, 'Columns')
            ?? $entryLength;
        $colors = (new PdfDictionaryReader())->getInteger($decodeParms, 'Colors') ?? 1;
        $bitsPerComponent = (new PdfDictionaryReader())->getInteger($decodeParms, 'BitsPerComponent') ?? 8;

        if ($columns <= 0) {
            throw new RuntimeException('DecodeParms de xref stream com /Columns invalido.');
        }

        if ($predictor === 2) {
            return $this->decodeTiffPredictor($stream, $columns, $colors, $bitsPerComponent);
        }

        if ($predictor < 10 || $predictor > 15) {
            throw new RuntimeException("DecodeParms de xref stream com /Predictor nao suportado: {$predictor}.");
        }

        return $this->decodePngPredictor($stream, $columns, $colors, $bitsPerComponent);
    }

    private function decodeTiffPredictor(
        string $stream,
        int $columns,
        int $colors,
        int $bitsPerComponent
    ): string {
        $rowLength = $this->predictorRowLength($columns, $colors, $bitsPerComponent);
        $bytesPerPixel = $this->predictorBytesPerPixel($colors, $bitsPerComponent);
        $decoded = '';

        for ($offset = 0; $offset < strlen($stream); $offset += $rowLength) {
            $row = substr($stream, $offset, $rowLength);

            if (strlen($row) !== $rowLength) {
                throw new RuntimeException('XRef stream com TIFF predictor truncado.');
            }

            $decoded .= $this->decodePngSubRow($row, $bytesPerPixel);
        }

        return $decoded;
    }

    private function decodePngPredictor(
        string $stream,
        int $columns,
        int $colors,
        int $bitsPerComponent
    ): string {
        $rowLength = $this->predictorRowLength($columns, $colors, $bitsPerComponent);
        $bytesPerPixel = $this->predictorBytesPerPixel($colors, $bitsPerComponent);
        $decoded = '';
        $previous = str_repeat("\0", $rowLength);
        $encodedRowLength = $rowLength + 1;

        for ($offset = 0; $offset < strlen($stream); $offset += $encodedRowLength) {
            $encoded = substr($stream, $offset, $encodedRowLength);

            if (strlen($encoded) !== $encodedRowLength) {
                throw new RuntimeException('XRef stream com PNG predictor truncado.');
            }

            $filter = ord($encoded[0]);
            $row = substr($encoded, 1);

            $decodedRow = match ($filter) {
                0 => $row,
                1 => $this->decodePngSubRow($row, $bytesPerPixel),
                2 => $this->decodePngUpRow($row, $previous),
                3 => $this->decodePngAverageRow($row, $previous, $bytesPerPixel),
                4 => $this->decodePngPaethRow($row, $previous, $bytesPerPixel),
                default => throw new RuntimeException("Filtro PNG predictor invalido em xref stream: {$filter}."),
            };

            $decoded .= $decodedRow;
            $previous = $decodedRow;
        }

        return $decoded;
    }

    private function decodePngSubRow(string $row, int $bytesPerPixel): string
    {
        $decoded = '';

        for ($i = 0; $i < strlen($row); $i++) {
            $left = $i >= $bytesPerPixel ? ord($decoded[$i - $bytesPerPixel]) : 0;
            $decoded .= chr((ord($row[$i]) + $left) & 0xff);
        }

        return $decoded;
    }

    private function decodePngUpRow(string $row, string $previous): string
    {
        $decoded = '';

        for ($i = 0; $i < strlen($row); $i++) {
            $decoded .= chr((ord($row[$i]) + ord($previous[$i])) & 0xff);
        }

        return $decoded;
    }

    private function decodePngAverageRow(string $row, string $previous, int $bytesPerPixel): string
    {
        $decoded = '';

        for ($i = 0; $i < strlen($row); $i++) {
            $left = $i >= $bytesPerPixel ? ord($decoded[$i - $bytesPerPixel]) : 0;
            $up = ord($previous[$i]);
            $decoded .= chr((ord($row[$i]) + intdiv($left + $up, 2)) & 0xff);
        }

        return $decoded;
    }

    private function decodePngPaethRow(string $row, string $previous, int $bytesPerPixel): string
    {
        $decoded = '';

        for ($i = 0; $i < strlen($row); $i++) {
            $left = $i >= $bytesPerPixel ? ord($decoded[$i - $bytesPerPixel]) : 0;
            $up = ord($previous[$i]);
            $upperLeft = $i >= $bytesPerPixel ? ord($previous[$i - $bytesPerPixel]) : 0;
            $decoded .= chr((ord($row[$i]) + $this->paethPredictor($left, $up, $upperLeft)) & 0xff);
        }

        return $decoded;
    }

    private function paethPredictor(int $left, int $up, int $upperLeft): int
    {
        $estimate = $left + $up - $upperLeft;
        $leftDistance = abs($estimate - $left);
        $upDistance = abs($estimate - $up);
        $upperLeftDistance = abs($estimate - $upperLeft);

        if ($leftDistance <= $upDistance && $leftDistance <= $upperLeftDistance) {
            return $left;
        }

        return $upDistance <= $upperLeftDistance ? $up : $upperLeft;
    }

    private function predictorRowLength(int $columns, int $colors, int $bitsPerComponent): int
    {
        return (int) ceil(($columns * $colors * $bitsPerComponent) / 8);
    }

    private function predictorBytesPerPixel(int $colors, int $bitsPerComponent): int
    {
        return max(1, (int) ceil(($colors * $bitsPerComponent) / 8));
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

        $decoded = $encoded;

        foreach ($this->streamFilters($this->streamDictionary($object->body)) as $filter) {
            $decoded = $this->applyFilter($decoded, $filter);

            if (strlen($decoded) > self::MAX_DECODED_STREAM_BYTES) {
                throw new RuntimeException('Stream PDF decodificado excede o limite suportado.');
            }
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
        return (new PdfDictionaryReader())->getInteger($dictionary, $name);
    }

    /**
     * @return array<int>
     */
    private function dictionaryIntegerArray(string $dictionary, string $name): array
    {
        return (new PdfDictionaryReader())->getIntegerArray($dictionary, $name);
    }

    /**
     * @return array<string>
     */
    private function streamFilters(string $dictionary): array
    {
        $value = trim((new PdfDictionaryReader())->getValue($dictionary, 'Filter') ?? '');

        if ($value === '') {
            return [];
        }

        if (str_starts_with($value, '/')) {
            return [substr($value, 1)];
        }

        if (! str_starts_with($value, '[') || ! str_ends_with($value, ']')) {
            throw new RuntimeException('Filtro de stream PDF invalido.');
        }

        $filters = [];
        $parts = preg_split('/\s+/', trim(substr($value, 1, -1)));

        if ($parts === false) {
            return [];
        }

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (! str_starts_with($part, '/')) {
                throw new RuntimeException('Filtro de stream PDF invalido.');
            }

            $filters[] = substr($part, 1);
        }

        return $filters;
    }

    private function applyFilter(string $content, string $filter): string
    {
        return match ($filter) {
            'FlateDecode', 'Fl' => $this->flateDecode($content),
            'ASCIIHexDecode', 'AHx' => $this->asciiHexDecode($content),
            'ASCII85Decode', 'A85' => $this->ascii85Decode($content),
            'RunLengthDecode', 'RL' => $this->runLengthDecode($content),
            default => throw new RuntimeException("Stream PDF com filtro nao suportado: {$filter}."),
        };
    }

    private function flateDecode(string $content): string
    {
        $decoded = gzuncompress($content);

        if ($decoded === false) {
            throw new RuntimeException('Nao foi possivel descomprimir FlateDecode.');
        }

        return $decoded;
    }

    private function asciiHexDecode(string $content): string
    {
        $hex = '';

        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];

            if ($char === '>') {
                break;
            }

            if (preg_match('/\s/', $char) === 1) {
                continue;
            }

            if (! ctype_xdigit($char)) {
                throw new RuntimeException('ASCIIHexDecode invalido.');
            }

            $hex .= $char;
        }

        if (strlen($hex) % 2 === 1) {
            $hex .= '0';
        }

        $decoded = hex2bin($hex);

        if ($decoded === false) {
            throw new RuntimeException('ASCIIHexDecode invalido.');
        }

        return $decoded;
    }

    private function ascii85Decode(string $content): string
    {
        if (function_exists('convert_uudecode')) {
            // PDF ASCII85 is not uuencode; keep the explicit decoder below.
        }

        $data = preg_replace('/\s+/', '', $content);
        $data = str_replace(['<~', '~>'], '', $data ?? '');
        $decoded = '';
        $tuple = [];

        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];

            if ($char === 'z' && $tuple === []) {
                $decoded .= "\0\0\0\0";
                continue;
            }

            $ord = ord($char);

            if ($ord < 33 || $ord > 117) {
                throw new RuntimeException('ASCII85Decode invalido.');
            }

            $tuple[] = $ord - 33;

            if (count($tuple) === 5) {
                $decoded .= $this->ascii85Tuple($tuple, 4);
                $tuple = [];
            }
        }

        if ($tuple !== []) {
            $bytes = count($tuple) - 1;

            while (count($tuple) < 5) {
                $tuple[] = 84;
            }

            $decoded .= $this->ascii85Tuple($tuple, $bytes);
        }

        return $decoded;
    }

    /**
     * @param array<int> $tuple
     */
    private function ascii85Tuple(array $tuple, int $bytes): string
    {
        $value = 0;

        foreach ($tuple as $part) {
            $value = ($value * 85) + $part;
        }

        $chunk = pack('N', $value);

        return substr($chunk, 0, $bytes);
    }

    private function runLengthDecode(string $content): string
    {
        $decoded = '';
        $offset = 0;

        while ($offset < strlen($content)) {
            $length = ord($content[$offset++]);

            if ($length === 128) {
                break;
            }

            if ($length <= 127) {
                $count = $length + 1;
                $decoded .= substr($content, $offset, $count);
                $offset += $count;
                continue;
            }

            $count = 257 - $length;
            $byte = $content[$offset++] ?? '';
            $decoded .= str_repeat($byte, $count);
        }

        return $decoded;
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
