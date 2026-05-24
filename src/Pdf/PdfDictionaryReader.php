<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfDictionaryReader
{
    public function getReference(string $dictionary, string $name): ?string
    {
        $value = $this->getValue($dictionary, $name);

        if ($value === null) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($value));

        if (
            $parts === false
            || count($parts) < 3
            || ! ctype_digit($parts[0])
            || ! ctype_digit($parts[1])
            || $parts[2] !== 'R'
        ) {
            return null;
        }

        return "{$parts[0]} {$parts[1]} R";
    }

    public function getInteger(string $dictionary, string $name): ?int
    {
        $value = trim($this->getValue($dictionary, $name) ?? '');

        return preg_match('/^\d+$/', $value) === 1
            ? (int) $value
            : null;
    }

    /**
     * @return array<int>
     */
    public function getIntegerArray(string $dictionary, string $name): array
    {
        $value = trim($this->getValue($dictionary, $name) ?? '');

        if (! str_starts_with($value, '[') || ! str_ends_with($value, ']')) {
            return [];
        }

        $content = trim(substr($value, 1, -1));

        if ($content === '') {
            return [];
        }

        $parts = preg_split('/\s+/', $content);

        if ($parts === false) {
            return [];
        }

        $integers = [];

        foreach ($parts as $part) {
            if (! ctype_digit($part)) {
                return [];
            }

            $integers[] = (int) $part;
        }

        return $integers;
    }

    public function hasNameValue(string $dictionary, string $name, string $value): bool
    {
        return trim($this->getValue($dictionary, $name) ?? '') === '/' . $value;
    }

    public function getValue(string $dictionary, string $name): ?string
    {
        $offset = $this->findNameOffset($dictionary, $name);

        if ($offset === null) {
            return null;
        }

        $valueStart = $this->skipWhitespace($dictionary, $offset + strlen($name) + 1);
        $valueEnd = $this->valueEnd($dictionary, $valueStart);

        return substr($dictionary, $valueStart, $valueEnd - $valueStart);
    }

    private function findNameOffset(string $dictionary, string $name): ?int
    {
        $offset = 0;
        $length = strlen($dictionary);
        $dictionaryDepth = 0;
        $arrayDepth = 0;

        while ($offset < $length) {
            $char = $dictionary[$offset];

            if ($char === '(') {
                $offset = $this->literalStringEnd($dictionary, $offset) + 1;
                continue;
            }

            if ($char === '<' && ($dictionary[$offset + 1] ?? '') !== '<') {
                $offset = $this->hexStringEnd($dictionary, $offset) + 1;
                continue;
            }

            if ($char === '%') {
                $offset = $this->commentEnd($dictionary, $offset);
                continue;
            }

            if (substr($dictionary, $offset, 2) === '<<') {
                $dictionaryDepth++;
                $offset += 2;
                continue;
            }

            if (substr($dictionary, $offset, 2) === '>>') {
                $dictionaryDepth = max(0, $dictionaryDepth - 1);
                $offset += 2;
                continue;
            }

            if ($char === '[') {
                $arrayDepth++;
                $offset++;
                continue;
            }

            if ($char === ']') {
                $arrayDepth = max(0, $arrayDepth - 1);
                $offset++;
                continue;
            }

            if ($char !== '/') {
                $offset++;
                continue;
            }

            $nameEnd = $offset + 1;

            while ($nameEnd < $length && ! $this->isDelimiter($dictionary[$nameEnd])) {
                $nameEnd++;
            }

            $isCurrentDictionaryLevel = $arrayDepth === 0 && $dictionaryDepth <= 1;

            if ($isCurrentDictionaryLevel && substr($dictionary, $offset + 1, $nameEnd - $offset - 1) === $name) {
                return $offset;
            }

            $offset = $nameEnd;
        }

        return null;
    }

    private function valueEnd(string $dictionary, int $offset): int
    {
        if (substr($dictionary, $offset, 2) === '<<') {
            return $this->balancedEnd($dictionary, $offset, '<<', '>>') + 2;
        }

        $char = $dictionary[$offset] ?? '';

        if ($char === '[') {
            return $this->balancedEnd($dictionary, $offset, '[', ']') + 1;
        }

        if ($char === '(') {
            return $this->literalStringEnd($dictionary, $offset) + 1;
        }

        if ($char === '<') {
            return $this->hexStringEnd($dictionary, $offset) + 1;
        }

        if ($char === '/') {
            $end = $offset + 1;

            while ($end < strlen($dictionary) && ! $this->isDelimiter($dictionary[$end])) {
                $end++;
            }

            return $end;
        }

        $end = $offset;

        while ($end < strlen($dictionary) && ! $this->isDelimiter($dictionary[$end])) {
            $end++;
        }

        $afterFirstToken = $this->skipWhitespace($dictionary, $end);
        $secondEnd = $this->readIntegerEnd($dictionary, $afterFirstToken);

        if ($secondEnd !== null) {
            $afterSecondToken = $this->skipWhitespace($dictionary, $secondEnd);

            if (($dictionary[$afterSecondToken] ?? '') === 'R') {
                return $afterSecondToken + 1;
            }
        }

        return $end;
    }

    private function balancedEnd(string $value, int $start, string $open, string $close): int
    {
        $depth = 0;
        $offset = $start;
        $openLength = strlen($open);
        $closeLength = strlen($close);

        while ($offset < strlen($value)) {
            if ($value[$offset] === '(') {
                $offset = $this->literalStringEnd($value, $offset) + 1;
                continue;
            }

            if ($value[$offset] === '<' && ($value[$offset + 1] ?? '') !== '<') {
                $offset = $this->hexStringEnd($value, $offset) + 1;
                continue;
            }

            if ($value[$offset] === '%') {
                $offset = $this->commentEnd($value, $offset);
                continue;
            }

            if (substr($value, $offset, $openLength) === $open) {
                $depth++;
                $offset += $openLength;
                continue;
            }

            if (substr($value, $offset, $closeLength) === $close) {
                $depth--;

                if ($depth === 0) {
                    return $offset;
                }

                $offset += $closeLength;
                continue;
            }

            $offset++;
        }

        return strlen($value);
    }

    private function literalStringEnd(string $value, int $offset): int
    {
        $depth = 0;

        while ($offset < strlen($value)) {
            $char = $value[$offset];

            if ($char === '\\') {
                $offset += 2;
                continue;
            }

            if ($char === '(') {
                $depth++;
            }

            if ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    return $offset;
                }
            }

            $offset++;
        }

        return strlen($value);
    }

    private function hexStringEnd(string $value, int $offset): int
    {
        $end = strpos($value, '>', $offset + 1);

        return $end === false ? strlen($value) : $end;
    }

    private function commentEnd(string $value, int $offset): int
    {
        while ($offset < strlen($value) && ! in_array($value[$offset], ["\r", "\n"], true)) {
            $offset++;
        }

        return $offset;
    }

    private function readIntegerEnd(string $value, int $offset): ?int
    {
        if (! ctype_digit($value[$offset] ?? '')) {
            return null;
        }

        while ($offset < strlen($value) && ctype_digit($value[$offset])) {
            $offset++;
        }

        return $offset;
    }

    private function skipWhitespace(string $value, int $offset): int
    {
        while ($offset < strlen($value) && $this->isWhitespace($value[$offset])) {
            $offset++;
        }

        return $offset;
    }

    private function isDelimiter(string $value): bool
    {
        return $value === '' || $this->isWhitespace($value) || str_contains('()<>[]{}/%', $value);
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
