<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use RuntimeException;
use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\CmsSigner;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;

final readonly class RealPdfSigner
{
    public function sign(
        string $inputPdf,
        string $outputPdf,
        ?string $certificatePath = null,
        ?string $certificatePassword = null,
        ?TimestampClientInterface $timestampClient = null
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
            $signatureObjectNumber => $this->signatureObject(),

            $widgetObjectNumber => (new PdfSignatureWidget())
                ->build(
                    signatureObjectNumber: $signatureObjectNumber,
                    pageObjectNumber: $pageNumber
                ),

            $acroFormObjectNumber => (new PdfAcroForm())
                ->build(
                    widgetObjectNumber: $widgetObjectNumber
                ),

            $catalogNumber => $updatedCatalog,

            $pageNumber => $updatedPage,
        ];

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
                timestampClient: $timestampClient
            );
        }


        $success = file_put_contents($outputPdf, $updated);

        if ($success === false) {
            throw new RuntimeException("Não foi possível salvar o PDF assinado: {$outputPdf}");
        }
    }

    private function signatureObject(): string
    {
        $contents = new PdfSignatureContents(
            reservedBytes: 8192
        );

        $date = gmdate('YmdHis');

        return "<<\n"
            . "/Type /Sig\n"
            . "/Filter /Adobe.PPKLite\n"
            . "/SubFilter /adbe.pkcs7.detached\n"
            . "/ByteRange [********** ********** ********** **********]\n"
            . "/Contents <" . $contents->placeholder() . ">\n"
            . "/M (D:{$date}+00'00')\n"
            . "/Name (PAdES Core)\n"
            . "/Reason (Document signed digitally)\n"
            . ">>";
    }

    private function applySignature(
        string $pdfContent,
        string $certificatePath,
        string $certificatePassword,
        ?TimestampClientInterface $timestampClient = null
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
            timestampClient: $timestampClient
        )
        )->signDetachedDer(
            $signedData
        );

        $hexSignature = (new PdfSignatureContents())
            ->encode($cms);

        return $signaturePlaceholder->replaceContents(
            $pdfContent,
            $hexSignature
        );
    }
}
