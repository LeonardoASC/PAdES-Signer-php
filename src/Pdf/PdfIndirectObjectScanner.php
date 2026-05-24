<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfIndirectObjectScanner
{
    private const int MAX_OBJECTS = 100000;
    private const int MAX_OBJECT_BODY_BYTES = 104857600;

    /**
     * @return array<int, PdfIndirectObject>
     */
    public function scan(string $pdfContent): array
    {
        $objects = [];
        $offset = 0;

        while (($header = $this->findObjectHeader($pdfContent, $offset)) !== null) {
            $bodyEnd = $this->findObjectEnd($pdfContent, $header['bodyStart']);
            $bodyLength = $bodyEnd - $header['bodyStart'];

            if ($bodyLength > self::MAX_OBJECT_BODY_BYTES) {
                throw new RuntimeException('Objeto PDF excede o limite de tamanho suportado.');
            }

            $body = trim(substr($pdfContent, $header['bodyStart'], $bodyEnd - $header['bodyStart']));

            $objects[$header['number']] = new PdfIndirectObject(
                number: $header['number'],
                generation: $header['generation'],
                offset: $header['offset'],
                body: $body
            );

            if (count($objects) > self::MAX_OBJECTS) {
                throw new RuntimeException('PDF excede o limite de objetos suportado.');
            }

            $offset = $bodyEnd + strlen('endobj');
        }

        ksort($objects);

        return $objects;
    }

    /**
     * @return array{number:int,generation:int,offset:int,bodyStart:int}|null
     */
    private function findObjectHeader(string $pdfContent, int $offset): ?array
    {
        $length = strlen($pdfContent);

        while (($objOffset = strpos($pdfContent, 'obj', $offset)) !== false) {
            $afterObj = $objOffset + 3;

            if (
                ! $this->isDelimiter($this->charAt($pdfContent, $objOffset - 1))
                || ! $this->isDelimiter($this->charAt($pdfContent, $afterObj))
            ) {
                $offset = $afterObj;
                continue;
            }

            $generationEnd = $this->skipWhitespaceBackward($pdfContent, $objOffset - 1);
            $generationStart = $this->readIntegerStart($pdfContent, $generationEnd);

            if ($generationStart === null) {
                $offset = $afterObj;
                continue;
            }

            $numberEnd = $this->skipWhitespaceBackward($pdfContent, $generationStart - 1);
            $numberStart = $this->readIntegerStart($pdfContent, $numberEnd);

            if ($numberStart === null) {
                $offset = $afterObj;
                continue;
            }

            if (! $this->isDelimiter($this->charAt($pdfContent, $numberStart - 1))) {
                $offset = $afterObj;
                continue;
            }

            $bodyStart = $this->skipLineEnding($pdfContent, $afterObj);

            if ($bodyStart > $length) {
                throw new RuntimeException('Cabecalho de objeto PDF truncado.');
            }

            return [
                'number' => (int) substr($pdfContent, $numberStart, $numberEnd - $numberStart + 1),
                'generation' => (int) substr($pdfContent, $generationStart, $generationEnd - $generationStart + 1),
                'offset' => $numberStart,
                'bodyStart' => $bodyStart,
            ];
        }

        return null;
    }

    private function findObjectEnd(string $pdfContent, int $bodyStart): int
    {
        $cursor = $bodyStart;

        while (true) {
            $streamOffset = strpos($pdfContent, 'stream', $cursor);
            $endObjectOffset = strpos($pdfContent, 'endobj', $cursor);

            if ($endObjectOffset === false) {
                throw new RuntimeException('Objeto PDF sem endobj.');
            }

            if ($streamOffset === false || $endObjectOffset < $streamOffset) {
                return $endObjectOffset;
            }

            $streamDataStart = $this->skipStreamLineEnding($pdfContent, $streamOffset + strlen('stream'));
            $streamDataEnd = $this->streamDataEnd($pdfContent, $bodyStart, $streamOffset, $streamDataStart);
            $endStreamOffset = strpos($pdfContent, 'endstream', $streamDataEnd);

            if ($endStreamOffset === false) {
                throw new RuntimeException('Stream PDF sem endstream.');
            }

            $cursor = $endStreamOffset + strlen('endstream');
        }
    }

    private function streamDataEnd(
        string $pdfContent,
        int $bodyStart,
        int $streamOffset,
        int $streamDataStart
    ): int {
        $dictionary = substr($pdfContent, $bodyStart, $streamOffset - $bodyStart);
        $length = $this->directLength($dictionary);

        if ($length === null) {
            $endStreamOffset = strpos($pdfContent, 'endstream', $streamDataStart);

            if ($endStreamOffset === false) {
                throw new RuntimeException('Stream PDF sem endstream.');
            }

            return $endStreamOffset;
        }

        return $streamDataStart + $length;
    }

    private function directLength(string $dictionary): ?int
    {
        return (new PdfDictionaryReader())->getInteger($dictionary, 'Length');
    }

    private function readIntegerStart(string $value, int $end): ?int
    {
        if ($end < 0 || ! ctype_digit($value[$end])) {
            return null;
        }

        $start = $end;

        while ($start > 0 && ctype_digit($value[$start - 1])) {
            $start--;
        }

        return $start;
    }

    private function skipWhitespaceBackward(string $value, int $offset): int
    {
        while ($offset >= 0 && $this->isWhitespace($value[$offset])) {
            $offset--;
        }

        return $offset;
    }

    private function skipWhitespaceForward(string $value, int $offset): int
    {
        while ($offset < strlen($value) && $this->isWhitespace($value[$offset])) {
            $offset++;
        }

        return $offset;
    }

    private function skipLineEnding(string $value, int $offset): int
    {
        while ($offset < strlen($value) && ($value[$offset] === ' ' || $value[$offset] === "\t")) {
            $offset++;
        }

        return $this->skipStreamLineEnding($value, $offset);
    }

    private function skipStreamLineEnding(string $value, int $offset): int
    {
        if (substr($value, $offset, 2) === "\r\n") {
            return $offset + 2;
        }

        if (($value[$offset] ?? '') === "\n" || ($value[$offset] ?? '') === "\r") {
            return $offset + 1;
        }

        return $offset;
    }

    private function isDelimiter(string $value): bool
    {
        return $value === '' || $this->isWhitespace($value) || str_contains('()<>[]{}/%', $value);
    }

    private function charAt(string $value, int $offset): string
    {
        return $offset >= 0 && $offset < strlen($value)
            ? $value[$offset]
            : '';
    }

    private function isWhitespace(string $value): bool
    {
        return $value === "\0"
            || $value === "\t"
            || $value === "\n"
            || $value === "\f"
            || $value === "\r"
            || $value === ' ';
    }
}
