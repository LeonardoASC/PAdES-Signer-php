<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureWidget
{
    public function build(
        int $signatureObjectNumber,
        int $pageObjectNumber = 3
    ): string {
        return "<<\n"
            . "/Type /Annot\n"
            . "/Subtype /Widget\n"
            . "/FT /Sig\n"
            . "/Rect [0 0 0 0]\n"
            . "/V {$signatureObjectNumber} 0 R\n"
            . "/T (Signature1)\n"
            . "/F 4\n"
            . "/P {$pageObjectNumber} 0 R\n"
            . ">>";
    }
}