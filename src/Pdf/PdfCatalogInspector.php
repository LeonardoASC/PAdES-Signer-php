<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfCatalogInspector
{
    public function getCatalogObjectNumber(string $pdfContent): int
    {
        if (! preg_match('/(\d+)\s+0\s+obj\s*<<(?:(?!endobj).)*\/Type\s*\/Catalog(?:(?!endobj).)*>>\s*endobj/s', $pdfContent, $matches)) {
            throw new RuntimeException('Objeto /Catalog não encontrado.');
        }

        return (int) $matches[1];
    }

    public function getCatalogObjectBody(string $pdfContent): string
    {
        if (! preg_match('/\d+\s+0\s+obj\s*(<<(?:(?!endobj).)*\/Type\s*\/Catalog(?:(?!endobj).)*>>)\s*endobj/s', $pdfContent, $matches)) {
            throw new RuntimeException('Corpo do objeto /Catalog não encontrado.');
        }

        return $matches[1];
    }
}