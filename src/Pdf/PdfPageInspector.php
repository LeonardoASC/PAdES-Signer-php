<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfPageInspector
{
    public function getFirstPageObjectNumber(string $pdfContent): int
    {
        return $this->firstPageObject($pdfContent)->number;
    }

    public function getFirstPageObjectBody(string $pdfContent): string
    {
        return $this->firstPageObject($pdfContent)->body;
    }

    private function firstPageObject(string $pdfContent): PdfIndirectObject
    {
        $structure = (new PdfStructuralParser())->parse($pdfContent);

        foreach ($structure->objects as $object) {
            if (preg_match('/\/Type\s*\/Page\b/s', $object->body) === 1) {
                return $object;
            }
        }

        throw new RuntimeException('Objeto /Page nao encontrado.');
    }
}
