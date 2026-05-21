<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfCatalogInspector
{
    public function getCatalogObjectNumber(string $pdfContent): int
    {
        return $this->latestCatalogObject($pdfContent)->number;
    }

    public function getCatalogObjectBody(string $pdfContent): string
    {
        return $this->latestCatalogObject($pdfContent)->body;
    }

    private function latestCatalogObject(string $pdfContent): PdfIndirectObject
    {
        $structure = (new PdfStructuralParser())->parse($pdfContent);

        try {
            $rootReference = $structure->latestTrailer()->getReference('Root');
        } catch (RuntimeException) {
            $rootReference = null;
        }

        if ($rootReference !== null) {
            [$objectNumber] = array_map('intval', explode(' ', $rootReference));

            try {
                return $structure->getObject($objectNumber);
            } catch (RuntimeException) {
                // Minimal fixtures may omit a valid xref/trailer root target.
            }
        }

        foreach (array_reverse($structure->objects, preserve_keys: true) as $object) {
            if (str_contains($object->body, '/Type') && str_contains($object->body, '/Catalog')) {
                return $object;
            }
        }

        throw new RuntimeException('Objeto /Catalog nao encontrado.');
    }
}
