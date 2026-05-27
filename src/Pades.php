<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Exception\CertificateException;
use NihilLabs\Pades\Exception\InvalidPadesArgumentException;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use RuntimeException;

final readonly class Pades
{
    public static function sign(
        string $inputPdf,
        string $outputPdf,
        string $certificatePath,
        string $certificatePassword,
        PadesSignatureOptions $options
    ): PadesSignatureResult {
        self::assertRequiredSignatureMetadata($options);

        try {
            $credential = new PfxSignatureCredential(
                pathOrCertificate: $certificatePath,
                password: $certificatePassword
            );
        } catch (RuntimeException $exception) {
            throw new CertificateException(
                'Nao foi possivel carregar o certificado PFX/P12.',
                previous: $exception
            );
        }

        return (new PadesSigner())->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            credential: $credential,
            options: $options
        );
    }

    public static function signWithPfxContents(
        string $inputPdf,
        string $outputPdf,
        string $certificateContents,
        string $certificatePassword,
        PadesSignatureOptions $options
    ): PadesSignatureResult {
        self::assertRequiredSignatureMetadata($options);

        try {
            $credential = PfxSignatureCredential::fromContents(
                contents: $certificateContents,
                password: $certificatePassword
            );
        } catch (RuntimeException $exception) {
            throw new CertificateException(
                'Nao foi possivel carregar o certificado PFX/P12 em memoria.',
                previous: $exception
            );
        }

        return (new PadesSigner())->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            credential: $credential,
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
                throw new InvalidPadesArgumentException(
                    "Informe {$field} em PadesSignatureOptions."
                );
            }
        }
    }
}
