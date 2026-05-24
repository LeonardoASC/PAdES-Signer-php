<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\IncrementalPdfWriter;
use NihilLabs\Pades\Pdf\PdfStructuralParser;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use NihilLabs\Pades\Validation\PdfIncrementalIntegrityAnalyzer;
use PHPUnit\Framework\TestCase;

final class PdfIncrementalIntegrityAnalyzerTest extends TestCase
{
    public function test_it_allows_legitimate_signature_revision(): void
    {
        $firstSignedPath = SignedPdfFixture::signedPdfPath('incremental-integrity-signature-first');
        $secondSignedPath = SignedPdfFixture::outputPath('incremental-integrity-signature-second.pdf');

        $this->signAgain($firstSignedPath, $secondSignedPath);

        $pdf = file_get_contents($secondSignedPath);
        $this->assertNotFalse($pdf);

        $report = (new PdfIncrementalIntegrityAnalyzer())->analyze($pdf);

        $this->assertTrue($report->valid, implode("\n", $report->messages));
        $this->assertTrue($report->checks['revision_diff_policy']);
        $this->assertTrue($report->checks['changes_classified']);
        $this->assertTrue($report->checks['signed_objects_not_replaced']);
        $this->assertTrue($report->checks['xref_revision_coverage']);
    }

    public function test_it_allows_dss_and_document_timestamp_updates(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent('incremental-integrity-dss');
        $nextObject = $this->nextObjectNumber($pdf);

        $updated = (new IncrementalPdfWriter())->appendObjects($pdf, [
            $nextObject => '<< /Type /DSS /Certs [] /OCSPs [] /CRLs [] >>',
            $nextObject + 1 => "<< /Type /Sig /Filter /Adobe.PPKLite /SubFilter /ETSI.RFC3161 /Contents <00> /ByteRange [0 1 2 3] >>",
        ]);

        $report = (new PdfIncrementalIntegrityAnalyzer())->analyze($updated);

        $this->assertTrue($report->valid, implode("\n", $report->messages));
        $this->assertTrue($report->checks['dss_and_timestamp_updates_allowed']);
    }

    public function test_it_rejects_suspicious_replacement_of_signed_object(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent('incremental-integrity-malicious');

        $updated = (new IncrementalPdfWriter())->appendObject(
            pdfContent: $pdf,
            objectNumber: 3,
            objectBody: '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents (tampered) >>'
        );

        $report = (new PdfIncrementalIntegrityAnalyzer())->analyze($updated);

        $this->assertFalse($report->valid);
        $this->assertFalse($report->checks['signed_objects_not_replaced']);
        $this->assertFalse($report->checks['malicious_incremental_replacement_not_detected']);
        $this->assertNotEmpty($report->messages);
    }

    public function test_doc_mdp_permission_one_rejects_new_signature_revision(): void
    {
        $input = SignedPdfFixture::outputPath('incremental-integrity-docmdp-input.pdf');
        $certified = SignedPdfFixture::outputPath('incremental-integrity-docmdp-certified.pdf');
        $second = SignedPdfFixture::outputPath('incremental-integrity-docmdp-second.pdf');

        (new \NihilLabs\Pades\Pdf\MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $certified,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456',
            signatureType: 'certification',
            certificationPermission: 1
        );

        $this->signAgain($certified, $second);

        $pdf = file_get_contents($second);
        $this->assertNotFalse($pdf);

        $report = (new PdfIncrementalIntegrityAnalyzer())->analyze($pdf);

        $this->assertFalse($report->valid);
        $this->assertFalse($report->checks['doc_mdp_allowed']);
    }

    public function test_field_mdp_all_rejects_later_field_change(): void
    {
        $input = SignedPdfFixture::outputPath('incremental-integrity-fieldmdp-input.pdf');
        $locked = SignedPdfFixture::outputPath('incremental-integrity-fieldmdp-locked.pdf');
        $second = SignedPdfFixture::outputPath('incremental-integrity-fieldmdp-second.pdf');

        (new \NihilLabs\Pades\Pdf\MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $locked,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456',
            fieldLockAction: 'All'
        );

        $this->signAgain($locked, $second);

        $pdf = file_get_contents($second);
        $this->assertNotFalse($pdf);

        $report = (new PdfIncrementalIntegrityAnalyzer())->analyze($pdf);

        $this->assertFalse($report->valid);
        $this->assertFalse($report->checks['field_mdp_allowed']);
    }

    public function test_it_rejects_invalid_xref_revision_chain(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent('incremental-integrity-xref');
        $updated = (new IncrementalPdfWriter())->appendObject(
            pdfContent: $pdf,
            objectNumber: $this->nextObjectNumber($pdf),
            objectBody: '<< /Type /DSS >>'
        );

        $corrupted = preg_replace('/\/Prev\s+\d+/', '/Prev 999999', $updated, 1);
        $this->assertIsString($corrupted);

        $report = (new PdfIncrementalIntegrityAnalyzer())->analyze($corrupted);

        $this->assertFalse($report->valid);
        $this->assertFalse($report->checks['xref_revision_coverage']);
    }

    private function signAgain(string $inputPath, string $outputPath): void
    {
        (new RealPdfSigner())->sign(
            inputPdf: $inputPath,
            outputPdf: $outputPath,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456',
            signatureName: 'Second Signer'
        );
    }

    private function nextObjectNumber(string $pdf): int
    {
        return (new PdfStructuralParser())->parse($pdf)->highestObjectNumber() + 1;
    }
}
