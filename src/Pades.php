<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Exception\InvalidPadesArgumentException;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;

final readonly class Pades
{
    public static function signBb(
        string $inputPdf,
        string $outputPdf,
        SignatureCredentialInterface $credential,
        PadesSignatureOptions $options
    ): PadesSignatureResult {
        self::assertRequiredSignatureMetadata($options);

        if ($options->timestampProvider !== null) {
            throw new InvalidPadesArgumentException(
                'Use Pades::signBt para assinar com timestamp TSA.'
            );
        }

        return (new PadesSigner())->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            credential: $credential,
            options: $options
        );
    }

    public static function signBt(
        string $inputPdf,
        string $outputPdf,
        SignatureCredentialInterface $credential,
        TimestampProviderInterface $timestampProvider,
        PadesSignatureOptions $options
    ): PadesSignatureResult {
        self::assertRequiredSignatureMetadata($options);

        return (new PadesSigner())->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            credential: $credential,
            options: self::withTimestampProvider($options, $timestampProvider)
        );
    }

    public static function addLt(
        string $inputPdf,
        string $outputPdf,
        LtvValidationMaterial $material
    ): void {
        (new PadesLtvEnricher())->addLtFile(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            material: $material
        );
    }

    public static function addLta(
        string $inputPdf,
        string $outputPdf,
        TimestampProviderInterface $timestampProvider,
        ?string $fieldName = null
    ): void {
        (new PadesLtvEnricher())->addLtaFile(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            timestampProvider: $timestampProvider,
            fieldName: $fieldName
        );
    }

    private static function withTimestampProvider(
        PadesSignatureOptions $options,
        TimestampProviderInterface $timestampProvider
    ): PadesSignatureOptions {
        return new PadesSignatureOptions(
            timestampProvider: $timestampProvider,
            trustValidator: $options->trustValidator,
            signerProvider: $options->signerProvider,
            visibleSignature: $options->visibleSignature,
            signatureRect: $options->signatureRect,
            signatureFlags: $options->signatureFlags,
            signatureName: $options->signatureName,
            signatureReason: $options->signatureReason,
            signatureLocation: $options->signatureLocation,
            signatureContactInfo: $options->signatureContactInfo,
            signatureFieldName: $options->signatureFieldName,
            signatureType: $options->signatureType,
            certificationPermission: $options->certificationPermission,
            lockedFieldNames: $options->lockedFieldNames,
            fieldLockAction: $options->fieldLockAction,
            hashAlgorithm: $options->hashAlgorithm,
            signatureAlgorithm: $options->signatureAlgorithm,
            minimumHashAlgorithm: $options->minimumHashAlgorithm,
            maxInputPdfBytes: $options->maxInputPdfBytes,
            includeSigningTime: $options->includeSigningTime,
            appendSignaturePage: $options->appendSignaturePage,
            signaturePageMediaBox: $options->signaturePageMediaBox,
            signaturePageRect: $options->signaturePageRect
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
