<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfObjectInspector
{
    public function getHighestObjectNumber(string $pdfContent): int
    {
        if (! preg_match_all('/\b(\d+)\s+(\d+)\s+obj\b/', $pdfContent, $matches)) {
            throw new RuntimeException('Nenhum objeto PDF encontrado.');
        }

        $numbers = array_map('intval', $matches[1]);

        return max($numbers);
    }

    public function getNextObjectNumber(string $pdfContent): int
    {
        return $this->getHighestObjectNumber($pdfContent) + 1;
    }
}