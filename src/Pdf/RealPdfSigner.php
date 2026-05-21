<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use RuntimeException;
use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Internal\Crypto\PadesCmsSigner;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Signing\SignerProviderInterface;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;

final readonly class RealPdfSigner
{
    private const SIGNATURE_RESERVED_BYTES = 65536;

    public function sign(
        string $inputPdf,
        string $outputPdf,
        ?string $certificatePath = null,
        ?string $certificatePassword = null,
        ?TimestampProviderInterface $timestampClient = null,
        bool $visibleSignature = false,
        array $signatureRect = [48, 48, 547, 96],
        int $signatureFlags = 132,
        string $signatureName = 'PAdES Core',
        string $signatureReason = 'Document signed digitally',
        ?string $signatureLocation = null,
        ?string $signatureContactInfo = null,
        ?SignatureCredentialInterface $signatureCredential = null,
        ?SignerProviderInterface $signerProvider = null,
        ?string $signatureFieldName = null,
        string $signatureType = 'approval',
        int $certificationPermission = 2,
        array $lockedFieldNames = [],
        string $fieldLockAction = 'Include',
        ?SignatureAlgorithmPolicy $algorithmPolicy = null
    ): void {
        if (! file_exists($inputPdf)) {
            throw new InvalidArgumentException("PDF de entrada não encontrado: {$inputPdf}");
        }

        $content = file_get_contents($inputPdf);

        if ($content === false || ! str_starts_with($content, '%PDF-')) {
            throw new InvalidArgumentException('Arquivo de entrada não é um PDF válido.');
        }

        if (! in_array($signatureType, ['approval', 'certification'], true)) {
            throw new InvalidArgumentException('Tipo de assinatura PDF deve ser approval ou certification.');
        }

        $algorithmPolicy ??= SignatureAlgorithmPolicy::default();

        $isCertificationSignature = $signatureType === 'certification';

        if ($isCertificationSignature && (new PdfSignatureFieldInspector())->hasSignatures($content)) {
            throw new RuntimeException('Assinatura de certificacao deve ser a primeira assinatura do PDF.');
        }

        $structure = (new PdfStructuralParser())->parse($content);
        $fieldLocator = new PdfSignatureFieldLocator();
        $existingSignatureField = $signatureFieldName === null
            ? null
            : $fieldLocator->findEmptySignatureField($structure, $signatureFieldName);

        $objectInspector = new PdfObjectInspector();

        $nextObjectNumber = $objectInspector
            ->getNextObjectNumber($content);

        $signatureObjectNumber = $nextObjectNumber;
        $widgetObjectNumber = $existingSignatureField?->objectNumber ?? $nextObjectNumber + 1;
        $nextAvailableObjectNumber = $existingSignatureField === null
            ? $nextObjectNumber + 2
            : $nextObjectNumber + 1;
        $appearanceObjectNumber = $visibleSignature
            ? $nextAvailableObjectNumber
            : null;

        $catalogInspector = new PdfCatalogInspector();

        $catalogNumber = $catalogInspector
            ->getCatalogObjectNumber($content);

        $catalogBody = $catalogInspector
            ->getCatalogObjectBody($content);

        $catalogUpdater = new PdfCatalogUpdater();
        $acroFormObjectNumber = (new PdfAcroFormInspector())
            ->getAcroFormObjectNumber($catalogBody);

        $updatedCatalog = $catalogUpdater->ensureEtsiExtension($catalogBody);

        $updatedAcroForm = null;

        if ($acroFormObjectNumber === null) {
            $acroFormObjectNumber = $nextAvailableObjectNumber;
            $nextAvailableObjectNumber++;

            if ($appearanceObjectNumber !== null) {
                $appearanceObjectNumber = $nextAvailableObjectNumber;
            }

            $updatedCatalog = $catalogUpdater->addAcroForm(
                catalogBody: $catalogBody,
                acroFormObjectNumber: $acroFormObjectNumber
            );
        } elseif ($existingSignatureField === null) {
            $acroFormBody = $structure->getObject($acroFormObjectNumber)->body;

            $updatedAcroForm = (new PdfAcroFormUpdater())
                ->addSignatureField(
                    acroFormBody: $acroFormBody,
                    widgetObjectNumber: $widgetObjectNumber
                );
        }

        $pageInspector = new PdfPageInspector();

        $pageNumber = $pageInspector
            ->getFirstPageObjectNumber($content);

        $pageBody = $pageInspector
            ->getFirstPageObjectBody($content);

        $updatedPage = $existingSignatureField === null
            ? (new PdfPageUpdater())
                ->addAnnotation(
                    pageBody: $pageBody,
                    widgetObjectNumber: $widgetObjectNumber
                )
            : null;

        $fieldName = $signatureFieldName
            ?? $fieldLocator->nextAvailableFieldName($structure);

        if ($isCertificationSignature) {
            $updatedCatalog = $catalogUpdater->addDocMdpPermission(
                catalogBody: $updatedCatalog,
                signatureObjectNumber: $signatureObjectNumber
            );
        }

        $signatureReferences = (new PdfSignatureReferenceBuilder())->build(
            catalogObjectNumber: $catalogNumber,
            certificationPermission: $isCertificationSignature ? $certificationPermission : null,
            lockedFieldNames: $lockedFieldNames,
            fieldLockAction: $fieldLockAction
        );

        $objects = [
            $signatureObjectNumber => $this->signatureObject(
                name: $signatureName,
                reason: $signatureReason,
                location: $signatureLocation,
                contactInfo: $signatureContactInfo,
                references: $signatureReferences
            ),

            $catalogNumber => $updatedCatalog,
        ];

        if ($existingSignatureField === null) {
            $objects[$widgetObjectNumber] = (new PdfSignatureWidget())
                ->build(
                    signatureObjectNumber: $signatureObjectNumber,
                    pageObjectNumber: $pageNumber,
                    rect: $visibleSignature ? $signatureRect : [0, 0, 0, 0],
                    flags: $visibleSignature ? $signatureFlags : 4,
                    appearanceObjectNumber: $appearanceObjectNumber,
                    fieldName: $fieldName
                );

            $objects[$pageNumber] = $updatedPage;
        } else {
            $objects[$existingSignatureField->objectNumber] = $fieldLocator->withSignatureValue(
                fieldBody: $existingSignatureField->body,
                signatureObjectNumber: $signatureObjectNumber
            );
        }

        if ($updatedAcroForm !== null) {
            $objects[$acroFormObjectNumber] = $updatedAcroForm;
        } elseif ($acroFormObjectNumber >= $nextObjectNumber) {
            $objects[$acroFormObjectNumber] = (new PdfAcroForm())->build(
                widgetObjectNumber: $widgetObjectNumber
            );
        }

        if ($appearanceObjectNumber !== null) {
            $objects[$appearanceObjectNumber] = (new PdfSignatureAppearance())
                ->build("Digitally signed by {$signatureName}");
        }

        $updated = (new IncrementalPdfWriter())
            ->appendObjects(
                pdfContent: $content,
                objects: $objects
            );

        if ($signatureCredential === null && $certificatePath !== null && $certificatePassword !== null) {
            $signatureCredential = new PfxSignatureCredential(
                pathOrCertificate: $certificatePath,
                password: $certificatePassword
            );
        }

        if ($signatureCredential !== null) {
            $updated = $this->applySignature(
                pdfContent: $updated,
                signatureCredential: $signatureCredential,
                timestampClient: $timestampClient,
                signerProvider: $signerProvider,
                algorithmPolicy: $algorithmPolicy
            );
        }


        $success = file_put_contents($outputPdf, $updated);

        if ($success === false) {
            throw new RuntimeException("Não foi possível salvar o PDF assinado: {$outputPdf}");
        }
    }

    private function signatureObject(
        string $name = 'PAdES Core',
        string $reason = 'Document signed digitally',
        ?string $location = null,
        ?string $contactInfo = null,
        string $references = ''
    ): string
    {
        $contents = new PdfSignatureContents(
            reservedBytes: self::SIGNATURE_RESERVED_BYTES
        );

        $date = gmdate('YmdHis');

        $signature = "<<\n"
            . "/Type /Sig\n"
            . "/Filter /Adobe.PPKLite\n"
            . "/SubFilter /ETSI.CAdES.detached\n"
            . "/ByteRange [********** ********** ********** **********]\n"
            . "/Contents <" . $contents->placeholder() . ">\n"
            . "/M (D:{$date}+00'00')\n"
            . "/Name " . $this->pdfString($name) . "\n"
            . "/Reason " . $this->pdfString($reason) . "\n";

        if ($references !== '') {
            $signature .= $references;
        }

        if ($location !== null) {
            $signature .= "/Location " . $this->pdfString($location) . "\n";
        }

        if ($contactInfo !== null) {
            $signature .= "/ContactInfo " . $this->pdfString($contactInfo) . "\n";
        }

        return $signature . ">>";
    }

    private function pdfString(string $value): string
    {
        return '(' . str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $value
        ) . ')';
    }

    private function applySignature(
        string $pdfContent,
        SignatureCredentialInterface $signatureCredential,
        ?TimestampProviderInterface $timestampClient = null,
        ?SignerProviderInterface $signerProvider = null,
        SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy()
    ): string {
        $signaturePlaceholder = new PdfSignaturePlaceholder();

        $contentsRange = $signaturePlaceholder->findContentsRange($pdfContent);

        $byteRangeCalculator = new ByteRangeCalculator();

        $byteRange = $byteRangeCalculator->calculate(
            pdfContent: $pdfContent,
            contentsStart: $contentsRange['start'],
            contentsEnd: $contentsRange['end']
        );

        $pdfContent = (new PdfByteRangePlaceholder())
            ->replace($pdfContent, $byteRange);

        $signedData = $byteRangeCalculator->extractSignedData(
            $pdfContent,
            $byteRange
        );

        $cms = (new PadesCmsSigner(
            certificate: $signatureCredential,
            timestampClient: $timestampClient,
            signerProvider: $signerProvider,
            algorithmPolicy: $algorithmPolicy
        ))->signPdfByteRangeData(
            $signedData
        );

        $hexSignature = (new PdfSignatureContents(
            reservedBytes: self::SIGNATURE_RESERVED_BYTES
        ))
            ->encode($cms);

        return $signaturePlaceholder->replaceContents(
            $pdfContent,
            $hexSignature
        );
    }
}
