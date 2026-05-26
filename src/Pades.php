<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Signing\PfxSignatureCredential;

final readonly class Pades
{
    public static function sign(
        string $inputPdf,
        string $outputPdf,
        string $certificatePath,
        string $certificatePassword,
        ?PadesSignatureOptions $options = null
    ): void {
        $options ??= new PadesSignatureOptions(
            visibleSignature: true,
            signatureName: 'Admin User',
            signatureReason: 'Assinatura digital de documento assistencial',
            signatureLocation: 'Prontuario Eletronico MPTO',
            signatureContactInfo: 'admin@example.com',
            appendSignaturePage: true
        );

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
        ?PadesSignatureOptions $options = null
    ): void {
        $options ??= new PadesSignatureOptions(
            visibleSignature: true,
            signatureName: 'Admin User',
            signatureReason: 'Assinatura digital de documento assistencial',
            signatureLocation: 'Prontuario Eletronico MPTO',
            signatureContactInfo: 'admin@example.com',
            appendSignaturePage: true
        );

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
}
