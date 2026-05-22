<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampRequest;
use NihilLabs\Pades\Crypto\Timestamp\TimestampResponseParser;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;
use RuntimeException;

final readonly class PdfDocTimeStampSigner
{
    private const SIGNATURE_RESERVED_BYTES = 65536;

    public function sign(
        string $inputPdf,
        string $outputPdf,
        TimestampProviderInterface $timestampClient,
        ?string $signatureFieldName = null
    ): void {
        if (! file_exists($inputPdf)) {
            throw new InvalidArgumentException("PDF de entrada nao encontrado: {$inputPdf}");
        }

        $content = file_get_contents($inputPdf);

        if ($content === false || ! str_starts_with($content, '%PDF-')) {
            throw new InvalidArgumentException('Arquivo de entrada nao e um PDF valido.');
        }

        $updated = $this->signContent(
            pdfContent: $content,
            timestampClient: $timestampClient,
            signatureFieldName: $signatureFieldName
        );

        if (file_put_contents($outputPdf, $updated) === false) {
            throw new RuntimeException("Nao foi possivel salvar o PDF com DocTimeStamp: {$outputPdf}");
        }
    }

    public function signContent(
        string $pdfContent,
        TimestampProviderInterface $timestampClient,
        ?string $signatureFieldName = null
    ): string {
        if (! str_starts_with($pdfContent, '%PDF-')) {
            throw new InvalidArgumentException('Conteudo de entrada nao e um PDF valido.');
        }

        $content = $pdfContent;
        $structure = (new PdfStructuralParser())->parse($content);
        $objectInspector = new PdfObjectInspector();
        $catalogInspector = new PdfCatalogInspector();
        $catalogNumber = $catalogInspector->getCatalogObjectNumber($content);
        $catalogBody = $catalogInspector->getCatalogObjectBody($content);
        $catalogUpdater = new PdfCatalogUpdater();
        $updatedCatalog = $catalogUpdater->ensureEtsiExtension($catalogBody);
        $acroFormObjectNumber = (new PdfAcroFormInspector())->getAcroFormObjectNumber($catalogBody);
        $nextObjectNumber = $objectInspector->getNextObjectNumber($content);
        $signatureObjectNumber = $nextObjectNumber;
        $widgetObjectNumber = $nextObjectNumber + 1;
        $nextAvailableObjectNumber = $nextObjectNumber + 2;
        $updatedAcroForm = null;

        if ($acroFormObjectNumber === null) {
            $acroFormObjectNumber = $nextAvailableObjectNumber++;
            $updatedCatalog = $catalogUpdater->addAcroForm(
                catalogBody: $catalogBody,
                acroFormObjectNumber: $acroFormObjectNumber
            );
        } else {
            $acroFormBody = $structure->getObject($acroFormObjectNumber)->body;
            $updatedAcroForm = (new PdfAcroFormUpdater())->addSignatureField(
                acroFormBody: $acroFormBody,
                widgetObjectNumber: $widgetObjectNumber
            );
        }

        $pageNumber = (new PdfPageInspector())->getFirstPageObjectNumber($content);
        $pageBody = (new PdfPageInspector())->getFirstPageObjectBody($content);
        $fieldName = $signatureFieldName
            ?? (new PdfSignatureFieldLocator())->nextAvailableFieldName($structure);

        $objects = [
            $signatureObjectNumber => $this->timestampSignatureObject(),
            $catalogNumber => $updatedCatalog,
            $widgetObjectNumber => (new PdfSignatureWidget())->build(
                signatureObjectNumber: $signatureObjectNumber,
                pageObjectNumber: $pageNumber,
                rect: [0, 0, 0, 0],
                flags: 4,
                appearanceObjectNumber: null,
                fieldName: $fieldName
            ),
            $pageNumber => (new PdfPageUpdater())->addAnnotation(
                pageBody: $pageBody,
                widgetObjectNumber: $widgetObjectNumber
            ),
        ];

        if ($updatedAcroForm !== null) {
            $objects[$acroFormObjectNumber] = $updatedAcroForm;
        } elseif ($acroFormObjectNumber >= $nextObjectNumber) {
            $objects[$acroFormObjectNumber] = (new PdfAcroForm())->build(
                widgetObjectNumber: $widgetObjectNumber
            );
        }

        $updated = (new IncrementalPdfWriter())->appendObjects(
            pdfContent: $content,
            objects: $objects
        );

        return $this->applyDocTimeStamp($updated, $timestampClient);
    }

    private function timestampSignatureObject(): string
    {
        $date = gmdate('YmdHis');
        $contents = new PdfSignatureContents(
            reservedBytes: self::SIGNATURE_RESERVED_BYTES
        );

        return "<<\n"
            . "/Type /Sig\n"
            . "/Filter /Adobe.PPKLite\n"
            . "/SubFilter /ETSI.RFC3161\n"
            . "/ByteRange [********** ********** ********** **********]\n"
            . "/Contents <" . $contents->placeholder() . ">\n"
            . "/M (D:{$date}+00'00')\n"
            . ">>";
    }

    private function applyDocTimeStamp(
        string $pdfContent,
        TimestampProviderInterface $timestampClient
    ): string {
        $signaturePlaceholder = new PdfSignaturePlaceholder();
        $contentsRange = $signaturePlaceholder->findContentsObjectRange($pdfContent);
        $byteRangeCalculator = new ByteRangeCalculator();
        $byteRange = $byteRangeCalculator->calculate(
            pdfContent: $pdfContent,
            contentsStart: $contentsRange['start'],
            contentsEnd: $contentsRange['end']
        );
        $pdfContent = (new PdfByteRangePlaceholder())->replace($pdfContent, $byteRange);
        $signedData = $byteRangeCalculator->extractSignedData($pdfContent, $byteRange);
        $timestampResponse = $timestampClient->requestToken(
            (new Rfc3161TimestampRequest())->build($signedData)
        );
        $timestampToken = (new TimestampResponseParser())->extractToken($timestampResponse);
        $hexSignature = (new PdfSignatureContents(
            reservedBytes: self::SIGNATURE_RESERVED_BYTES
        ))->encode($timestampToken);

        return $signaturePlaceholder->replaceContents($pdfContent, $hexSignature);
    }
}
