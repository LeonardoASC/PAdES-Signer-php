<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfCatalogInspector
{
    public function getCatalogObjectNumber(string $pdfContent): int
    {
        $matches = $this->catalogMatches($pdfContent);
        $objectNumbers = array_column($matches, 'objectNumber');

        return (int) end($objectNumbers);
    }

    public function getCatalogObjectBody(string $pdfContent): string
    {
        $matches = $this->catalogMatches($pdfContent);
        $bodies = array_column($matches, 'body');

        return (string) end($bodies);
    }

    /**
     * @return array<int, array{objectNumber:int, body:string}>
     */
    private function catalogMatches(string $pdfContent): array
    {
        if (! preg_match_all(
            '/(\d+)\s+0\s+obj\s*(.*?)\s*endobj/s',
            $pdfContent,
            $matches,
            PREG_SET_ORDER
        )) {
            throw new RuntimeException('Objeto /Catalog nao encontrado.');
        }

        $catalogMatches = [];

        foreach ($matches as $match) {
            $body = $match[2];

            if (! str_contains($body, '/Type') || ! str_contains($body, '/Catalog')) {
                continue;
            }

            $catalogMatches[] = [
                'objectNumber' => (int) $match[1],
                'body' => $body,
            ];
        }

        if ($catalogMatches === []) {
            throw new RuntimeException('Objeto /Catalog nao encontrado.');
        }

        return $catalogMatches;
    }
}
