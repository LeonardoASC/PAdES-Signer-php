<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\Cades\IcpBrasilSignaturePolicy;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use NihilLabs\Pades\Pdf\IncrementalPdfWriter;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\PdfAcroForm;
use NihilLabs\Pades\Pdf\PdfByteRangePlaceholder;
use NihilLabs\Pades\Pdf\PdfCatalogInspector;
use NihilLabs\Pades\Pdf\PdfCatalogUpdater;
use NihilLabs\Pades\Pdf\PdfObjectInspector;
use NihilLabs\Pades\Pdf\PdfPageInspector;
use NihilLabs\Pades\Pdf\PdfPageUpdater;
use NihilLabs\Pades\Pdf\PdfSignatureContents;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Pdf\PdfSignaturePlaceholder;
use NihilLabs\Pades\Pdf\PdfSignatureWidget;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RealIcpPolicyPdfTest extends TestCase
{
    private const SIGNATURE_RESERVED_BYTES = 65536;

    public function test_it_signs_real_icp_pdf_with_placeholder_policy_attribute(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $input = RealIcpSignedPdfFixture::outputPath('icp-policy-input.pdf');
        $output = RealIcpSignedPdfFixture::outputPath('icp-policy-output.pdf');

        (new MinimalPdfGenerator())
            ->generate($input);

        $certificate = RealIcpSignedPdfFixture::certificate();
        $policy = IcpBrasilSignaturePolicy::adRtPdfPlaceholder(
            policyHash: str_repeat("\x44", 32),
            policyUri: 'https://example.test/icp-brasil/ad-rt-pdf-placeholder.der'
        );

        $signedPdf = $this->signPdfWithPolicy(
            inputPdf: $input,
            certificate: $certificate,
            policy: $policy
        );

        file_put_contents($output, $signedPdf);

        $this->assertFileExists($output);
        $this->assertStringContainsString('/ByteRange', $signedPdf);
        $this->assertStringContainsString('/ETSI.CAdES.detached', $signedPdf);

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($signedPdf);

        $this->assertStringContainsString(
            '2A864886F70D010910020F',
            strtoupper(bin2hex($cms))
        );

        $parts = SignedPdfFixture::detachedCmsParts($signedPdf);

        $this->assertTrue(
            (new \NihilLabs\Pades\Crypto\OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $parts['cms'],
                    signedData: $parts['signedData']
                )
        );
    }

    private function signPdfWithPolicy(
        string $inputPdf,
        PfxCertificate $certificate,
        IcpBrasilSignaturePolicy $policy
    ): string {
        $content = file_get_contents($inputPdf);

        if ($content === false || ! str_starts_with($content, '%PDF-')) {
            throw new RuntimeException('PDF ICP de entrada invalido.');
        }

        $objectInspector = new PdfObjectInspector();
        $nextObjectNumber = $objectInspector->getNextObjectNumber($content);
        $signatureObjectNumber = $nextObjectNumber;
        $widgetObjectNumber = $nextObjectNumber + 1;
        $acroFormObjectNumber = $nextObjectNumber + 2;

        $catalogInspector = new PdfCatalogInspector();
        $catalogNumber = $catalogInspector->getCatalogObjectNumber($content);
        $catalogBody = $catalogInspector->getCatalogObjectBody($content);

        $pageInspector = new PdfPageInspector();
        $pageNumber = $pageInspector->getFirstPageObjectNumber($content);
        $pageBody = $pageInspector->getFirstPageObjectBody($content);

        $updated = (new IncrementalPdfWriter())
            ->appendObjects(
                pdfContent: $content,
                objects: [
                    $signatureObjectNumber => $this->signatureObject(),
                    $widgetObjectNumber => (new PdfSignatureWidget())
                        ->build(
                            signatureObjectNumber: $signatureObjectNumber,
                            pageObjectNumber: $pageNumber
                        ),
                    $acroFormObjectNumber => (new PdfAcroForm())
                        ->build($widgetObjectNumber),
                    $catalogNumber => (new PdfCatalogUpdater())
                        ->addAcroForm(
                            catalogBody: $catalogBody,
                            acroFormObjectNumber: $acroFormObjectNumber
                        ),
                    $pageNumber => (new PdfPageUpdater())
                        ->addAnnotation(
                            pageBody: $pageBody,
                            widgetObjectNumber: $widgetObjectNumber
                        ),
                ]
            );

        return $this->applyPolicySignature(
            pdfContent: $updated,
            certificate: $certificate,
            policy: $policy
        );
    }

    private function applyPolicySignature(
        string $pdfContent,
        PfxCertificate $certificate,
        IcpBrasilSignaturePolicy $policy
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
            pdfContent: $pdfContent,
            byteRange: $byteRange
        );

        $cms = (new AdvancedCmsSigner(
            certificate: $certificate,
            signaturePolicy: $policy
        ))->signDetachedDer($signedData);

        $hexSignature = (new PdfSignatureContents(
            reservedBytes: self::SIGNATURE_RESERVED_BYTES
        ))->encode($cms);

        return $signaturePlaceholder->replaceContents(
            pdfContent: $pdfContent,
            hexSignature: $hexSignature
        );
    }

    private function signatureObject(): string
    {
        $contents = new PdfSignatureContents(
            reservedBytes: self::SIGNATURE_RESERVED_BYTES
        );

        $date = gmdate('YmdHis');

        return "<<\n"
            . "/Type /Sig\n"
            . "/Filter /Adobe.PPKLite\n"
            . "/SubFilter /ETSI.CAdES.detached\n"
            . "/ByteRange [********** ********** ********** **********]\n"
            . "/Contents <" . $contents->placeholder() . ">\n"
            . "/M (D:{$date}+00'00')\n"
            . "/Name (PAdES Core)\n"
            . "/Reason (Document signed digitally with placeholder ICP policy)\n"
            . ">>";
    }
}
