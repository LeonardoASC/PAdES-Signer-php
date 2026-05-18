<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use RuntimeException;
use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\CmsSigner;

final readonly class RealPdfSigner
{
    public function sign(
        string $inputPdf,
        string $outputPdf,
        ?string $certificatePath = null,
        ?string $certificatePassword = null
    ): void {
        if (! file_exists($inputPdf)) {
            throw new InvalidArgumentException("PDF de entrada não encontrado: {$inputPdf}");
        }

        $content = file_get_contents($inputPdf);

        if ($content === false || ! str_starts_with($content, '%PDF-')) {
            throw new InvalidArgumentException('Arquivo de entrada não é um PDF válido.');
        }

        $nextObjectNumber = (new PdfObjectInspector())
            ->getNextObjectNumber($content);

        $updated = (new IncrementalPdfWriter())
            ->appendObject(
                pdfContent: $content,
                objectNumber: $nextObjectNumber,
                objectBody: $this->signatureObject()
            );

        if ($certificatePath !== null && $certificatePassword !== null) {
            $updated = $this->applySignature(
                pdfContent: $updated,
                certificatePath: $certificatePath,
                certificatePassword: $certificatePassword
            );
        }

        $success = file_put_contents($outputPdf, $updated);

        if ($success === false) {
            throw new RuntimeException("Não foi possível salvar o PDF assinado: {$outputPdf}");
        }
    }

    private function signatureObject(): string
    {
        $contents = new PdfSignatureContents(reservedBytes: 8192);

        return "<<\n"
            . "/Type /Sig\n"
            . "/Filter /Adobe.PPKLite\n"
            . "/SubFilter /adbe.pkcs7.detached\n"
            . "/ByteRange [********** ********** ********** **********]\n"
            . "/Contents <" . $contents->placeholder() . ">\n"
            . ">>";
    }

    private function applySignature(
        string $pdfContent,
        string $certificatePath,
        string $certificatePassword
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

        $cms = (new CmsSigner($certificate))
            ->signDetachedDer($signedData);

        $hexSignature = (new PdfSignatureContents())
            ->encode($cms);

        return $signaturePlaceholder->replaceContents(
            $pdfContent,
            $hexSignature
        );
    }
}
