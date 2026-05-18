<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Pdf\RealPdfSigner;

final readonly class Pades
{
    public static function sign(
        string $inputPdf,
        string $outputPdf,
        string $certificatePath,
        string $certificatePassword
    ): void {
        (new RealPdfSigner())->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            certificatePath: $certificatePath,
            certificatePassword: $certificatePassword
        );
    }
}