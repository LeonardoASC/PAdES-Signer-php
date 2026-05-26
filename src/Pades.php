<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Signing\PfxSignatureCredential;
use InvalidArgumentException;

final readonly class Pades
{
    public static function sign(
        string $inputPdf,
        string $outputPdf,
        string $certificatePath,
        string $certificatePassword,
        PadesSignatureOptions $options
    ): void {
        self::assertRequiredSignatureMetadata($options);

        (new PadesSigner())->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            credential: new PfxSignatureCredential(
                pathOrCertificate: $certificatePath,
                password: $certificatePassword
            ),
            options: $options
        );
    }

    public static function signWithPfxContents(
        string $inputPdf,
        string $outputPdf,
        string $certificateContents,
        string $certificatePassword,
        PadesSignatureOptions $options
    ): void {
        self::assertRequiredSignatureMetadata($options);

        (new PadesSigner())->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            credential: PfxSignatureCredential::fromContents(
                contents: $certificateContents,
                password: $certificatePassword
            ),
            options: $options
        );
    }

    private static function assertRequiredSignatureMetadata(PadesSignatureOptions $options): void
    {
        $required = [
            'signatureName' => $options->signatureName,
            'signatureReason' => $options->signatureReason,
            'signatureLocation' => $options->signatureLocation,
            'signatureContactInfo' => $options->signatureContactInfo,
        ];

        foreach ($required as $field => $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException(
                    "Informe {$field} em PadesSignatureOptions."
                );
            }
        }
    }
}
