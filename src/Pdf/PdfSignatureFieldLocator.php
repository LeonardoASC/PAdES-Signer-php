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
            if (! $this->isSignatureField($structure, $object->body) || ! $this->isEmpty($structure, $object->body)) {
                continue;
            }

            $name = $this->fullyQualifiedFieldName($structure, $object->body);

            if ($fieldName !== null && $name !== $fieldName) {
                continue;
            }

            return new PdfSignatureField(
                objectNumber: $object->number,
                body: $object->body,
                name: $name,
                pageObjectNumber: $this->pageObjectNumber($object->body),
                seedValue: $this->seedValue($structure, $object->body),
                lock: $this->lock($structure, $object->body)
            );
        }

        return null;
    }

    public function nextAvailableFieldName(PdfDocumentStructure $structure, string $baseName = 'Signature'): string
    {
        $names = [];

        foreach ($structure->objects as $object) {
            $name = $this->fullyQualifiedFieldName($structure, $object->body);

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

    private function isSignatureField(PdfDocumentStructure $structure, string $body): bool
    {
        return $this->hasInheritedNameValue($structure, $body, 'FT', 'Sig')
            || (
                preg_match('/\/Subtype\s*\/Widget\b/s', $body) === 1
                && preg_match('/\/Type\s*\/Annot\b/s', $body) === 1
                && preg_match('/\/V\s+\d+\s+\d+\s+R\b/s', $body) === 1
            );
    }

    private function isEmpty(PdfDocumentStructure $structure, string $body): bool
    {
        $current = $body;

        for ($depth = 0; $depth < 16; $depth++) {
            if (preg_match('/\/V\s+(?!null\b)\d+\s+\d+\s+R\b/s', $current) === 1) {
                return false;
            }

            $parent = $this->parentBody($structure, $current);

            if ($parent === null) {
                return true;
            }

            $current = $parent;
        }

        return true;
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

    private function fullyQualifiedFieldName(PdfDocumentStructure $structure, string $body): ?string
    {
        $parts = [];
        $current = $body;

        for ($depth = 0; $depth < 16; $depth++) {
            $name = $this->fieldName($current);

            if ($name !== null) {
                array_unshift($parts, $name);
            }

            $parent = $this->parentBody($structure, $current);

            if ($parent === null) {
                break;
            }

            $current = $parent;
        }

        return $parts === [] ? null : implode('.', $parts);
    }

    private function seedValue(PdfDocumentStructure $structure, string $body): ?PdfSeedValueDictionary
    {
        $seedValue = $this->inheritedValue($structure, $body, 'SV');

        if ($seedValue === null) {
            return null;
        }

        if (preg_match('/^\s*(\d+)\s+\d+\s+R\s*$/', $seedValue, $matches) === 1) {
            $seedValue = $structure->resolveObject((int) $matches[1])->body;
        }

        return PdfSeedValueDictionary::fromDictionary($seedValue);
    }

    private function lock(PdfDocumentStructure $structure, string $body): ?PdfSignatureFieldLock
    {
        $lock = $this->inheritedValue($structure, $body, 'Lock');

        if ($lock === null) {
            return null;
        }

        if (preg_match('/^\s*(\d+)\s+\d+\s+R\s*$/', $lock, $matches) === 1) {
            $lock = $structure->resolveObject((int) $matches[1])->body;
        }

        $reader = new PdfDictionaryReader();
        $action = trim((string) $reader->getValue($lock, 'Action'));

        if (str_starts_with($action, '/')) {
            $action = substr($action, 1);
        }

        if (! in_array($action, ['All', 'Include', 'Exclude'], true)) {
            return null;
        }

        return new PdfSignatureFieldLock(
            action: $action,
            fieldNames: $this->stringArray($reader->getValue($lock, 'Fields'))
        );
    }

    private function hasInheritedNameValue(
        PdfDocumentStructure $structure,
        string $body,
        string $name,
        string $value
    ): bool {
        $current = $body;

        for ($depth = 0; $depth < 16; $depth++) {
            if ((new PdfDictionaryReader())->hasNameValue($current, $name, $value)) {
                return true;
            }

            $parent = $this->parentBody($structure, $current);

            if ($parent === null) {
                return false;
            }

            $current = $parent;
        }

        return false;
    }

    private function inheritedValue(PdfDocumentStructure $structure, string $body, string $name): ?string
    {
        $current = $body;

        for ($depth = 0; $depth < 16; $depth++) {
            $value = (new PdfDictionaryReader())->getValue($current, $name);

            if ($value !== null) {
                return $value;
            }

            $parent = $this->parentBody($structure, $current);

            if ($parent === null) {
                return null;
            }

            $current = $parent;
        }

        return null;
    }

    private function parentBody(PdfDocumentStructure $structure, string $body): ?string
    {
        $parent = (new PdfDictionaryReader())->getReference($body, 'Parent');

        if ($parent === null) {
            return null;
        }

        return $structure->resolveReference($parent)->body;
    }

    /**
     * @return array<string>
     */
    private function stringArray(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        preg_match_all('/\((.*?)(?<!\\\\)\)/s', $value, $matches);

        return array_map(
            static fn (string $item): string => str_replace(['\\)', '\\(', '\\\\'], [')', '(', '\\'], $item),
            $matches[1]
        );
    }
}
