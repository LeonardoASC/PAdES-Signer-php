<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use RuntimeException;
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
        ?SignerProviderInterface $signerProvider = null
    ): void {
        if (! file_exists($inputPdf)) {
            throw new InvalidArgumentException("PDF de entrada não encontrado: {$inputPdf}");
        }

        $content = file_get_contents($inputPdf);

        if ($content === false || ! str_starts_with($content, '%PDF-')) {
            throw new InvalidArgumentException('Arquivo de entrada não é um PDF válido.');
        }

        $objectInspector = new PdfObjectInspector();

        $nextObjectNumber = $objectInspector
            ->getNextObjectNumber($content);

        $signatureObjectNumber = $nextObjectNumber;
        $widgetObjectNumber = $nextObjectNumber + 1;
        $acroFormObjectNumber = $nextObjectNumber + 2;
        $appearanceObjectNumber = $visibleSignature
            ? $nextObjectNumber + 3
            : null;

        $catalogInspector = new PdfCatalogInspector();

        $catalogNumber = $catalogInspector
            ->getCatalogObjectNumber($content);

        $catalogBody = $catalogInspector
            ->getCatalogObjectBody($content);

        $updatedCatalog = (new PdfCatalogUpdater())
            ->addAcroForm(
                catalogBody: $catalogBody,
                acroFormObjectNumber: $acroFormObjectNumber
            );

        $pageInspector = new PdfPageInspector();

        $pageNumber = $pageInspector
            ->getFirstPageObjectNumber($content);

        $pageBody = $pageInspector
            ->getFirstPageObjectBody($content);

        $updatedPage = (new PdfPageUpdater())
            ->addAnnotation(
                pageBody: $pageBody,
                widgetObjectNumber: $widgetObjectNumber
            );

        $objects = [
            $signatureObjectNumber => $this->signatureObject(
                name: $signatureName,
                reason: $signatureReason,
                location: $signatureLocation,
                contactInfo: $signatureContactInfo
            ),

            $widgetObjectNumber => (new PdfSignatureWidget())
                ->build(
                    signatureObjectNumber: $signatureObjectNumber,
                    pageObjectNumber: $pageNumber,
                    rect: $visibleSignature ? $signatureRect : [0, 0, 0, 0],
                    flags: $visibleSignature ? $signatureFlags : 4,
                    appearanceObjectNumber: $appearanceObjectNumber
                ),

            $acroFormObjectNumber => (new PdfAcroForm())
                ->build(
                    widgetObjectNumber: $widgetObjectNumber
                ),

            $catalogNumber => $updatedCatalog,

            $pageNumber => $updatedPage,
        ];

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
                signerProvider: $signerProvider
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
        ?string $contactInfo = null
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
        ?SignerProviderInterface $signerProvider = null
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
            signerProvider: $signerProvider
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
