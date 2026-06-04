<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use NihilLabs\Pades\Validation\PdfSignatureValidator;
use PHPUnit\Framework\TestCase;

final class PdfSignatureValidatorTest extends TestCase
{
    public function test_it_generates_detailed_pdf_signature_validation_status(): void
    {
        $pdf = $this->realSignedPdfContent('pdf-signature-validator');

        $report = (new PdfSignatureValidator())->validateFirst($pdf);

        $this->assertTrue($report->valid, implode("\n", $report->messages));
        $this->assertSame('sha256', $report->digestAlgorithm);
        $this->assertSame($report->calculatedDigestHex, $report->cmsMessageDigestHex);
        $this->assertTrue($report->checks['byte_range_semantic']);
        $this->assertTrue($report->checks['signed_revision_digest_calculated']);
        $this->assertTrue($report->checks['cms_message_digest_matches']);
        $this->assertTrue($report->checks['cryptographic_signature']);
        $this->assertTrue($report->checks['signer_certificate_present']);
        $this->assertTrue($report->checks['signer_certificate_matches_signer_info']);
        $this->assertNotNull($report->signerCertificatePem);
        $this->assertTrue($report->checks['covers_whole_document']);
        $this->assertTrue($report->checks['no_unsigned_tail']);
    }

    public function test_it_reports_unsigned_tail(): void
    {
        $pdf = $this->realSignedPdfContent('pdf-signature-validator-tail') . "\n% unsigned tail";

        $report = (new PdfSignatureValidator())->validateFirst($pdf);

        $this->assertTrue($report->valid);
        $this->assertFalse($report->checks['covers_whole_document']);
        $this->assertFalse($report->checks['no_unsigned_tail']);
        $this->assertEmpty($report->messages);
    }

    public function test_it_validates_signatures_across_multiple_pdf_revisions(): void
    {
        $firstSignedPath = $this->realSignedPdfPath('pdf-signature-validator-multi-revision-first');
        $secondSignedPath = SignedPdfFixture::outputPath('pdf-signature-validator-multi-revision-second.pdf');

        (new RealPdfSigner())->sign(
            inputPdf: $firstSignedPath,
            outputPdf: $secondSignedPath,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: $this->certificatePassword(),
            signatureFieldName: 'Signature2'
        );

        $pdf = file_get_contents($secondSignedPath);
        $this->assertNotFalse($pdf);

        $reports = (new PdfSignatureValidator())->validateAll($pdf);

        $this->assertCount(2, $reports);
        $this->assertTrue($reports[0]->valid, implode("\n", $reports[0]->messages));
        $this->assertTrue($reports[1]->valid, implode("\n", $reports[1]->messages));
        $this->assertFalse($reports[0]->checks['covers_whole_document']);
        $this->assertTrue($reports[1]->checks['covers_whole_document']);
    }

    private function realSignedPdfContent(string $name): string
    {
        $pdf = file_get_contents($this->realSignedPdfPath($name));

        $this->assertNotFalse($pdf);

        return $pdf;
    }

    private function realSignedPdfPath(string $name): string
    {
        $input = SignedPdfFixture::outputPath("{$name}-input.pdf");
        $output = SignedPdfFixture::outputPath("{$name}-signed.pdf");

        (new MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: $this->certificatePassword()
        );

        return $output;
    }

    private function certificatePassword(): string
    {
        $password = getenv('PADES_INTEROP_PFX_PASSWORD');

        if (! is_string($password) || $password === '') {
            self::markTestSkipped(
                'Configure PADES_INTEROP_PFX_PASSWORD para rodar validacao criptografica com certificate.pfx local.'
            );
        }

        return $password;
    }
}
