<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use RuntimeException;

final readonly class PadesSigner
{
    public function __construct(
        private RealPdfSigner $pdfSigner = new RealPdfSigner()
    ) {}

    public function sign(
        string $inputPdf,
        string $outputPdf,
        ?SignatureCredentialInterface $credential = null,
        ?PadesSignatureOptions $options = null
    ): void {
        $options ??= new PadesSignatureOptions();

        if ($credential !== null && $options->trustValidator !== null) {
            $trust = $options->trustValidator->validateCredential($credential);

            if (! $trust->trusted) {
                throw new RuntimeException(
                    $trust->messages[0] ?? 'A credencial de assinatura nao e confiavel.'
                );
            }
        }

        $this->pdfSigner->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            timestampClient: $options->timestampProvider,
            visibleSignature: $options->visibleSignature,
            signatureRect: $options->signatureRect,
            signatureFlags: $options->signatureFlags,
            signatureName: $options->signatureName,
            signatureReason: $options->signatureReason,
            signatureLocation: $options->signatureLocation,
            signatureContactInfo: $options->signatureContactInfo,
            signatureCredential: $credential,
            signerProvider: $options->signerProvider,
            signatureFieldName: $options->signatureFieldName,
            signatureType: $options->signatureType,
            certificationPermission: $options->certificationPermission,
            lockedFieldNames: $options->lockedFieldNames,
            fieldLockAction: $options->fieldLockAction,
            algorithmPolicy: $options->algorithmPolicy(),
            includeSigningTime: $options->includeSigningTime,
            appendSignaturePage: $options->appendSignaturePage,
            signaturePageMediaBox: $options->signaturePageMediaBox,
            signaturePageRect: $options->signaturePageRect
        );
    }
}
