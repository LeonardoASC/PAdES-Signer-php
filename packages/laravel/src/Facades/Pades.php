<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \NihilLabs\Pades\PadesSignatureResult sign(string $inputPdf, string $outputPdf, string $certificatePassword, ?\NihilLabs\Pades\PadesSignatureOptions $options = null, ?string $certificatePath = null)
 * @method static \NihilLabs\Pades\PadesSignatureResult signWithPfxContents(string $inputPdf, string $outputPdf, string $certificateContents, string $certificatePassword, ?\NihilLabs\Pades\PadesSignatureOptions $options = null)
 * @method static \NihilLabs\Pades\PadesValidationResult validateFile(string $pdfPath)
 * @method static \NihilLabs\Pades\PadesValidator validator()
 */
final class Pades extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'pades';
    }
}
