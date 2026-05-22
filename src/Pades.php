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
            signatureContactInfo: 'admin@adm.com'
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
}
