<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfFontEmbeddingInspector
{
    /**
     * @return list<array{objectNumber:int, baseFont:string|null, embedded:bool}>
     */
    public function inspect(string $pdfContent): array
    {
        $structure = (new PdfStructuralParser())->parse($pdfContent);
        $reader = new PdfDictionaryReader();
        $fonts = [];

        foreach ($structure->objects as $object) {
            if (! $reader->hasNameValue($object->body, 'Type', 'Font')) {
                continue;
            }

            $descriptor = $reader->getValue($object->body, 'FontDescriptor');
            $descriptorReference = $reader->getReference($object->body, 'FontDescriptor');
            $descriptorBody = $descriptor ?? '';

            if ($descriptorReference !== null) {
                $descriptorObjectNumber = (int) strtok($descriptorReference, ' ');
                $descriptorBody = $structure->getObject($descriptorObjectNumber)->body;
            }

            $fonts[] = [
                'objectNumber' => $object->number,
                'baseFont' => $this->baseFont($object->body, $reader),
                'embedded' => $this->hasEmbeddedProgram($object->body)
                    || $this->hasEmbeddedProgram($descriptorBody),
            ];
        }

        return $fonts;
    }

    public function hasNonEmbeddedFonts(string $pdfContent): bool
    {
        foreach ($this->inspect($pdfContent) as $font) {
            if (! $font['embedded']) {
                return true;
            }
        }

        return false;
    }

    private function baseFont(string $fontBody, PdfDictionaryReader $reader): ?string
    {
        $baseFont = $reader->getValue($fontBody, 'BaseFont');

        if ($baseFont === null) {
            return null;
        }

        return trim(ltrim($baseFont, '/'));
    }

    private function hasEmbeddedProgram(string $body): bool
    {
        return preg_match('/\/FontFile(?:2|3)?\b/', $body) === 1;
    }
}
