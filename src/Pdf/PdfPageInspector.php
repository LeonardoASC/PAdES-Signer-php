<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfPageInspector
{
    public function getFirstPageObjectNumber(string $pdfContent): int
    {
        if (! preg_match('/(\d+)\s+0\s+obj\s*<<(?:(?!endobj).)*\/Type\s*\/Page\b(?:(?!endobj).)*>>\s*endobj/s', $pdfContent, $matches)) {
            throw new RuntimeException('Objeto /Page não encontrado.');
        }

        return (int) $matches[1];
    }

    public function getFirstPageObjectBody(string $pdfContent): string
    {
        if (! preg_match('/\d+\s+0\s+obj\s*(<<(?:(?!endobj).)*\/Type\s*\/Page\b(?:(?!endobj).)*>>)\s*endobj/s', $pdfContent, $matches)) {
            throw new RuntimeException('Corpo do objeto /Page não encontrado.');
        }

        return $matches[1];
    }
}