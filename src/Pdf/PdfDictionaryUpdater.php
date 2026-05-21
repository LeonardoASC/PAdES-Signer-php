<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfDictionaryUpdater
{
    public function appendReferenceToArray(
        string $dictionary,
        string $name,
        int $objectNumber,
        int $generation = 0
    ): string {
        $reference = "{$objectNumber} {$generation} R";
        $property = $this->findNameValue($dictionary, $name);

        if ($property === null) {
            return $this->insertBeforeDictionaryEnd(
                dictionary: $dictionary,
                insertion: "/{$name} [{$reference}]\n",
                errorMessage: "Nao foi possivel adicionar /{$name} ao dicionario PDF."
            );
        }

        $valueStart = $this->skipWhitespace($dictionary, $property['valueStart']);

        if (($dictionary[$valueStart] ?? '') !== '[') {
            throw new RuntimeException("/{$name} existente nao e um array direto.");
        }

        $arrayEnd = $this->findBalancedEnd($dictionary, $valueStart, '[', ']');
        $arrayContent = substr($dictionary, $valueStart + 1, $arrayEnd - $valueStart - 1);

        if ($this->arrayContainsReference($arrayContent, $objectNumber, $generation)) {
            return $dictionary;
        }

        $prefix = substr($dictionary, 0, $arrayEnd);
        $suffix = substr($dictionary, $arrayEnd);
        $separator = trim($arrayContent) === '' ? '' : ' ';

        return $prefix . $separator . $reference . $suffix;
    }

    public function ensureDictionaryEntry(
        string $dictionary,
        string $dictionaryName,
        string $entryName,
        string $entryBody
    ): string {
        $property = $this->findNameValue($dictionary, $dictionaryName);

        if ($property === null) {
            $entry = "/{$dictionaryName} <<\n/{$entryName} {$entryBody}\n>>\n";

            return $this->insertBeforeDictionaryEnd(
                dictionary: $dictionary,
                insertion: $entry,
                errorMessage: "Nao foi possivel adicionar /{$dictionaryName} ao dicionario PDF."
            );
        }

        $valueStart = $this->skipWhitespace($dictionary, $property['valueStart']);

        if (substr($dictionary, $valueStart, 2) !== '<<') {
            throw new RuntimeException("/{$dictionaryName} existente nao e um dicionario direto.");
        }

        $dictionaryEnd = $this->findBalancedEnd($dictionary, $valueStart, '<<', '>>');
        $nestedDictionary = substr($dictionary, $valueStart, $dictionaryEnd - $valueStart + 2);

        if ($this->findNameValue($nestedDictionary, $entryName) !== null) {
            return $dictionary;
        }

        return substr($dictionary, 0, $dictionaryEnd)
            . "/{$entryName} {$entryBody}\n"
            . substr($dictionary, $dictionaryEnd);
    }

    public function ensureNameInteger(string $dictionary, string $name, int $value): string
    {
        if ($this->findNameValue($dictionary, $name) !== null) {
            return $dictionary;
        }

        return $this->insertBeforeDictionaryEnd(
            dictionary: $dictionary,
            insertion: "/{$name} {$value}\n",
            errorMessage: "Nao foi possivel adicionar /{$name} ao dicionario PDF."
        );
    }

    public function setReference(string $dictionary, string $name, int $objectNumber, int $generation = 0): string
    {
        $reference = "{$objectNumber} {$generation} R";
        $property = $this->findNameValue($dictionary, $name);

        if ($property === null) {
            return $this->insertBeforeDictionaryEnd(
                dictionary: $dictionary,
                insertion: "/{$name} {$reference}\n",
                errorMessage: "Nao foi possivel adicionar /{$name} ao dicionario PDF."
            );
        }

        $valueStart = $this->skipWhitespace($dictionary, $property['valueStart']);

        if (! preg_match('/\G(?:\d+\s+\d+\s+R|null\b)/s', $dictionary, $matches, 0, $valueStart)) {
            throw new RuntimeException("/{$name} existente nao e uma referencia indireta.");
        }

        return substr($dictionary, 0, $valueStart)
            . $reference
            . substr($dictionary, $valueStart + strlen($matches[0]));
    }

    /**
     * @return array{nameStart:int,valueStart:int}|null
     */
    private function findNameValue(string $dictionary, string $name): ?array
    {
        if (! preg_match('/\/' . preg_quote($name, '/') . '\b/s', $dictionary, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        return [
            'nameStart' => $matches[0][1],
            'valueStart' => $matches[0][1] + strlen($matches[0][0]),
        ];
    }

    private function insertBeforeDictionaryEnd(string $dictionary, string $insertion, string $errorMessage): string
    {
        $end = strrpos(rtrim($dictionary), '>>');

        if ($end === false) {
            throw new RuntimeException($errorMessage);
        }

        return substr($dictionary, 0, $end)
            . $insertion
            . substr($dictionary, $end);
    }

    private function skipWhitespace(string $value, int $offset): int
    {
        while ($offset < strlen($value) && preg_match('/\s/', $value[$offset]) === 1) {
            $offset++;
        }

        return $offset;
    }

    private function findBalancedEnd(string $value, int $start, string $open, string $close): int
    {
        $depth = 0;
        $length = strlen($value);
        $openLength = strlen($open);
        $closeLength = strlen($close);

        for ($offset = $start; $offset < $length; $offset++) {
            if (substr($value, $offset, $openLength) === $open) {
                $depth++;
                $offset += $openLength - 1;
                continue;
            }

            if (substr($value, $offset, $closeLength) === $close) {
                $depth--;

                if ($depth === 0) {
                    return $offset;
                }

                $offset += $closeLength - 1;
            }
        }

        throw new RuntimeException('Dicionario PDF com delimitadores desbalanceados.');
    }

    private function arrayContainsReference(string $arrayContent, int $objectNumber, int $generation): bool
    {
        return preg_match(
            '/(?<!\d)' . preg_quote((string) $objectNumber, '/') . '\s+'
                . preg_quote((string) $generation, '/') . '\s+R\b/',
            $arrayContent
        ) === 1;
    }
}
