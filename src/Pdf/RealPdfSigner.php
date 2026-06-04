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
    private const SIGNATURE_RESERVED_BYTES = 12000;
    private const TIMESTAMPED_SIGNATURE_RESERVED_BYTES = 24000;

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
        ?SignatureAlgorithmPolicy $algorithmPolicy = null,
        ?int $maxInputPdfBytes = null,
        bool $includeSigningTime = false,
        bool $appendSignaturePage = false,
        array $signaturePageMediaBox = [0, 0, 595, 842],
        array $signaturePageRect = [48, 120, 547, 700]
    ): void {
        if (! file_exists($inputPdf)) {
            throw new InvalidArgumentException("PDF de entrada não encontrado: {$inputPdf}");
        }

        $content = (new PdfFileGuard())->read($inputPdf, $maxInputPdfBytes);

        if (! str_starts_with($content, '%PDF-')) {
            throw new InvalidArgumentException('Arquivo de entrada não é um PDF válido.');
        }

        if (! in_array($signatureType, ['approval', 'certification'], true)) {
            throw new InvalidArgumentException('Tipo de assinatura PDF deve ser approval ou certification.');
        }

        $algorithmPolicy ??= SignatureAlgorithmPolicy::default();

        if ($signatureCredential === null && $certificatePath !== null && $certificatePassword !== null) {
            $signatureCredential = new PfxSignatureCredential(
                pathOrCertificate: $certificatePath,
                password: $certificatePassword
            );
        }

        if ($signatureCredential === null) {
            throw new InvalidArgumentException(
                'Informe uma credencial de assinatura real ou certificatePath/certificatePassword.'
            );
        }

        $isCertificationSignature = $signatureType === 'certification';

        if ($isCertificationSignature && (new PdfSignatureFieldInspector())->hasSignatures($content)) {
            throw new RuntimeException('Assinatura de certificacao deve ser a primeira assinatura do PDF.');
        }

        $structure = (new PdfStructuralParser())->parse($content);
        $fieldLocator = new PdfSignatureFieldLocator();
        $existingSignatureField = $signatureFieldName === null
            ? null
            : $fieldLocator->findEmptySignatureField($structure, $signatureFieldName);

        if ($existingSignatureField !== null && $existingSignatureField->seedValue !== null) {
            $existingSignatureField->seedValue->validate(
                algorithmPolicy: $algorithmPolicy,
                signatureReason: $signatureReason,
                certificatePem: $signatureCredential?->getCertificatePem()
            );
        }

        $objectInspector = new PdfObjectInspector();

        $nextObjectNumber = $objectInspector
            ->getNextObjectNumber($content);

        $nextAvailableObjectNumber = $nextObjectNumber;
        $widgetObjectNumber = $existingSignatureField?->objectNumber ?? $nextAvailableObjectNumber++;

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

        $appearanceObjectNumber = $visibleSignature
            ? $nextAvailableObjectNumber++
            : null;

        $signaturePageObjectNumber = $appendSignaturePage && $visibleSignature && $existingSignatureField === null
            ? $nextAvailableObjectNumber++
            : null;

        $signatureObjectNumber = $nextAvailableObjectNumber++;

        if ($signaturePageObjectNumber !== null && $signatureRect === [48, 48, 547, 96]) {
            $signatureRect = $signaturePageRect;
        }

        $pageNumber = null;
        $updatedPage = null;
        $appendedSignaturePage = null;

        if ($signaturePageObjectNumber !== null) {
            $pageNumber = $signaturePageObjectNumber;
            $appendedSignaturePage = (new PdfSignaturePageAppender())
                ->append(
                    structure: $structure,
                    catalogBody: $updatedCatalog,
                    pageObjectNumber: $signaturePageObjectNumber,
                    widgetObjectNumber: $widgetObjectNumber,
                    mediaBox: $signaturePageMediaBox
                );
        } else {
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
        }

        $fieldName = $signatureFieldName
            ?? $fieldLocator->nextAvailableFieldName($structure);

        if ($isCertificationSignature) {
            $updatedCatalog = $catalogUpdater->addDocMdpPermission(
                catalogBody: $updatedCatalog,
                signatureObjectNumber: $signatureObjectNumber
            );
        }

        $fieldLock = $existingSignatureField?->lock;
        $fieldLockActionForReference = $fieldLock?->action ?? $fieldLockAction;
        $lockedFieldNamesForReference = $fieldLock !== null
            ? $fieldLock->fieldNames
            : $lockedFieldNames;

        $signatureReferences = (new PdfSignatureReferenceBuilder())->build(
            catalogObjectNumber: $catalogNumber,
            certificationPermission: $isCertificationSignature ? $certificationPermission : null,
            lockedFieldNames: $lockedFieldNamesForReference,
            fieldLockAction: $fieldLockActionForReference
        );
        $reservedBytes = $this->signatureReservedBytes($timestampClient);

        $objects = [
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

        if ($appendedSignaturePage !== null) {
            foreach ($appendedSignaturePage['pageTreeObjects'] as $objectNumber => $objectBody) {
                $objects[$objectNumber] = $objectBody;
            }

            $objects[$signaturePageObjectNumber] = $appendedSignaturePage['pageBody'];
        }

        if ($appearanceObjectNumber !== null) {
            $appearanceWidth = $signatureRect[2] - $signatureRect[0];
            $appearanceHeight = $signatureRect[3] - $signatureRect[1];

            $objects[$appearanceObjectNumber] = (new PdfSignatureAppearance())
                ->build(
                    text: $this->appearanceText(
                        name: $signatureName,
                        reason: $signatureReason,
                        location: $signatureLocation,
                        contactInfo: $signatureContactInfo
                    ),
                    width: $appearanceWidth,
                    height: $appearanceHeight
                );
        }

        $objects[$signatureObjectNumber] = $this->signatureObject(
            name: $signatureName,
            reason: $signatureReason,
            location: $signatureLocation,
            contactInfo: $signatureContactInfo,
            references: $signatureReferences,
            reservedBytes: $reservedBytes
        );

        $updated = (new IncrementalPdfWriter())
            ->appendObjects(
                pdfContent: $content,
                objects: $objects
            );

        $updated = $this->applySignature(
            pdfContent: $updated,
            signatureCredential: $signatureCredential,
            timestampClient: $timestampClient,
            signerProvider: $signerProvider,
            algorithmPolicy: $algorithmPolicy,
            reservedBytes: $reservedBytes,
            includeSigningTime: $includeSigningTime
        );


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
        string $references = '',
        int $reservedBytes = self::SIGNATURE_RESERVED_BYTES
    ): string
    {
        $contents = new PdfSignatureContents(
            reservedBytes: $reservedBytes
        );

        $date = $this->pdfDate();

        $signature = "<<\n"
            . "/Contents <" . $contents->placeholder() . ">\n"
            . "/ByteRange [********** ********** ********** **********]\n"
            . "/Type /Sig\n"
            . "/Filter /Adobe.PPKLite\n"
            . "/SubFilter /ETSI.CAdES.detached\n"
            . "/M ({$date})\n"
            . "/Name " . $this->pdfString($name) . "\n"
            . ($location !== null ? "/Location " . $this->pdfString($location) . "\n" : '')
            . "/Reason " . $this->pdfString($reason) . "\n";

        if ($references !== '') {
            $signature .= $references;
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

    private function appearanceText(
        string $name,
        string $reason,
        ?string $location,
        ?string $contactInfo
    ): string {
        $date = new \DateTimeImmutable(
            'now',
            new \DateTimeZone('America/Sao_Paulo')
        );

        return implode("\n", array_filter([
            'ASSINATURA DIGITAL',
            "Assinado por: {$name}",
            'Data: ' . $date->format('d/m/Y H:i:s O'),
            "Motivo: {$reason}",
            $location !== null ? "Local: {$location}" : null,
            $contactInfo !== null ? "Contato: {$contactInfo}" : null,
        ]));
    }

    private function pdfDate(): string
    {
        $date = new \DateTimeImmutable(
            'now',
            new \DateTimeZone('America/Sao_Paulo')
        );

        $offset = $date->format('O');
        $offsetSign = substr($offset, 0, 1);
        $offsetHour = substr($offset, 1, 2);
        $offsetMinute = substr($offset, 3, 2);

        return sprintf(
            'D\072%s\%s%s\047%s\047',
            $date->format('YmdHis'),
            $offsetSign === '-' ? '055' : '053',
            $offsetHour,
            $offsetMinute
        );
    }

    private function applySignature(
        string $pdfContent,
        SignatureCredentialInterface $signatureCredential,
        ?TimestampProviderInterface $timestampClient = null,
        ?SignerProviderInterface $signerProvider = null,
        SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy(),
        int $reservedBytes = self::SIGNATURE_RESERVED_BYTES,
        bool $includeSigningTime = false
    ): string {
        $signaturePlaceholder = new PdfSignaturePlaceholder();

        $contentsRange = $signaturePlaceholder->findContentsObjectRange($pdfContent);

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
            algorithmPolicy: $algorithmPolicy,
            includeSigningTime: $includeSigningTime
        ))->signPdfByteRangeData(
            $signedData
        );

        $hexSignature = (new PdfSignatureContents(
            reservedBytes: $reservedBytes
        ))
            ->encode($cms);

        return $signaturePlaceholder->replaceContents(
            $pdfContent,
            $hexSignature
        );
    }

    private function signatureReservedBytes(
        ?TimestampProviderInterface $timestampClient
    ): int {
        return $timestampClient === null
            ? self::SIGNATURE_RESERVED_BYTES
            : self::TIMESTAMPED_SIGNATURE_RESERVED_BYTES;
    }
}
