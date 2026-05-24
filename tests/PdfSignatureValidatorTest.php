<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use NihilLabs\Pades\Validation\PdfSignatureValidator;
use PHPUnit\Framework\TestCase;

final class PdfSignatureValidatorTest extends TestCase
{
    public function test_it_generates_detailed_pdf_signature_validation_status(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent('pdf-signature-validator');

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
        $pdf = SignedPdfFixture::signedPdfContent('pdf-signature-validator-tail') . "\n% unsigned tail";

        $report = (new PdfSignatureValidator())->validateFirst($pdf);

        $this->assertTrue($report->valid);
        $this->assertFalse($report->checks['covers_whole_document']);
        $this->assertFalse($report->checks['no_unsigned_tail']);
        $this->assertEmpty($report->messages);
    }

    public function test_it_validates_signatures_across_multiple_pdf_revisions(): void
    {
        $firstSignedPath = SignedPdfFixture::signedPdfPath('pdf-signature-validator-multi-revision-first');
        $secondSignedPath = SignedPdfFixture::outputPath('pdf-signature-validator-multi-revision-second.pdf');

        (new \NihilLabs\Pades\Pdf\RealPdfSigner())->sign(
            inputPdf: $firstSignedPath,
            outputPdf: $secondSignedPath,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456',
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
}
