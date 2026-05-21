<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureFieldInspector
{
    public function countSignatureDictionaries(string $pdfContent): int
    {
        return preg_match_all('/\/Type\s*\/Sig\b/', $pdfContent);
    }

    public function hasSignatures(string $pdfContent): bool
    {
        return $this->countSignatureDictionaries($pdfContent) > 0;
    }
}
