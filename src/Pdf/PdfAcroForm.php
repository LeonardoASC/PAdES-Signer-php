<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfAcroForm
{
    public function build(int $widgetObjectNumber): string
    {
        return "<<\n"
            . "/Fields [{$widgetObjectNumber} 0 R]\n"
            . "/SigFlags 3\n"
            . ">>";
    }
}