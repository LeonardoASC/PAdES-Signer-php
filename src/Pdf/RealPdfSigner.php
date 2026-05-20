<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use RuntimeException;
use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\CmsSigner;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Crypto\Cades\IcpBrasilSignaturePolicy;

final readonly class RealPdfSigner
{
    private const SIGNATURE_RESERVED_BYTES = 65536;

    public function sign(
        string $inputPdf,
        string $outputPdf,
        ?string $certificatePath = null,
        ?string $certificatePassword = null,
        ?TimestampClientInterface $timestampClient = null,
        ?IcpBrasilSignaturePolicy $signaturePolicy = null,
        bool $visibleSignature = false,
        array $signatureRect = [48, 48, 547, 96],
        int $signatureFlags = 132,
        string $signatureName = 'PAdES Core',
        string $signatureReason = 'Document signed digitally',
        ?string $signatureLocation = null,
        ?string $signatureContactInfo = null
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

        if ($certificatePath !== null && $certificatePassword !== null) {
            $updated = $this->applySignature(
                pdfContent: $updated,
                certificatePath: $certificatePath,
                certificatePassword: $certificatePassword,
                timestampClient: $timestampClient,
                signaturePolicy: $signaturePolicy
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
        string $certificatePath,
        string $certificatePassword,
        ?TimestampClientInterface $timestampClient = null,
        ?IcpBrasilSignaturePolicy $signaturePolicy = null
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


        $certificate = new PfxCertificate(
            path: $certificatePath,
            password: $certificatePassword
        );

        $cms = (new AdvancedCmsSigner(
            certificate: $certificate,
            timestampClient: $timestampClient,
            signaturePolicy: $signaturePolicy
        ))->signDetachedDer(
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
