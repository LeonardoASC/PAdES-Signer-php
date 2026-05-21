<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureFieldLocator
{
    public function findEmptySignatureField(
        PdfDocumentStructure $structure,
        ?string $fieldName = null
    ): ?PdfSignatureField {
        foreach ($structure->objects as $object) {
            if (! $this->isSignatureField($object->body) || ! $this->isEmpty($object->body)) {
                continue;
            }

            $name = $this->fieldName($object->body);

            if ($fieldName !== null && $name !== $fieldName) {
                continue;
            }

            return new PdfSignatureField(
                objectNumber: $object->number,
                body: $object->body,
                name: $name,
                pageObjectNumber: $this->pageObjectNumber($object->body)
            );
        }

        return null;
    }

    public function nextAvailableFieldName(PdfDocumentStructure $structure, string $baseName = 'Signature'): string
    {
        $names = [];

        foreach ($structure->objects as $object) {
            $name = $this->fieldName($object->body);

            if ($name !== null) {
                $names[$name] = true;
            }
        }

        for ($index = 1; ; $index++) {
            $candidate = "{$baseName}{$index}";

            if (! isset($names[$candidate])) {
                return $candidate;
            }
        }
    }

    public function withSignatureValue(string $fieldBody, int $signatureObjectNumber): string
    {
        return (new PdfDictionaryUpdater())->setReference(
            dictionary: $fieldBody,
            name: 'V',
            objectNumber: $signatureObjectNumber
        );
    }

    private function isSignatureField(string $body): bool
    {
        return preg_match('/\/FT\s*\/Sig\b/s', $body) === 1
            || (
                preg_match('/\/Subtype\s*\/Widget\b/s', $body) === 1
                && preg_match('/\/Type\s*\/Annot\b/s', $body) === 1
                && preg_match('/\/V\s+\d+\s+\d+\s+R\b/s', $body) === 1
            );
    }

    private function isEmpty(string $body): bool
    {
        return preg_match('/\/V\s+(?!null\b)\d+\s+\d+\s+R\b/s', $body) !== 1;
    }

    private function fieldName(string $body): ?string
    {
        if (! preg_match('/\/T\s*\((.*?)(?<!\\\\)\)/s', $body, $matches)) {
            return null;
        }

        return str_replace(
            ['\\)', '\\(', '\\\\'],
            [')', '(', '\\'],
            $matches[1]
        );
    }

    private function pageObjectNumber(string $body): ?int
    {
        if (! preg_match('/\/P\s+(\d+)\s+\d+\s+R\b/s', $body, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
