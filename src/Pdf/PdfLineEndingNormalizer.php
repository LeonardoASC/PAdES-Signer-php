<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfLineEndingNormalizer
{
    public function normalizeStructuralSegment(string $segment): string
    {
        return str_replace(["\r\n", "\r"], "\n", $segment);
    }
}
