<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfCatalogInspector
{
    public function getCatalogObjectNumber(string $pdfContent): int
    {
        $matches = $this->catalogMatches($pdfContent);
        $objectNumbers = $matches[1];

        return (int) end($objectNumbers);
    }

    public function getCatalogObjectBody(string $pdfContent): string
    {
        $matches = $this->catalogMatches($pdfContent);
        $bodies = $matches[2];

        return (string) end($bodies);
    }

    /**
     * @return array{0:array<string>,1:array<string>,2:array<string>}
     */
    private function catalogMatches(string $pdfContent): array
    {
        if (! preg_match_all(
            '/(\d+)\s+0\s+obj\s*(<<(?:(?!endobj).)*\/Type\s*\/Catalog(?:(?!endobj).)*>>)\s*endobj/s',
            $pdfContent,
            $matches
        )) {
            throw new RuntimeException('Objeto /Catalog nao encontrado.');
        }

        return $matches;
    }
}
