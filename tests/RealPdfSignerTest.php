<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use PHPUnit\Framework\TestCase;

final class RealPdfSignerTest extends TestCase
{
    public function test_it_accepts_a_real_pdf_and_creates_output(): void
    {
        $input = __DIR__ . '/Output/minimal.pdf';
        $output = __DIR__ . '/Output/minimal-signed.pdf';

        if (! is_dir(dirname($input))) {
            mkdir(dirname($input), 0777, true);
        }

        (new MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign($input, $output);

        $this->assertFileExists($output);

        $content = file_get_contents($output);

        $this->assertStringContainsString('%PDF-', $content);
        $this->assertStringContainsString('xref', $content);
        $this->assertStringContainsString('trailer', $content);
        $this->assertStringContainsString('/Prev ', $content);

        $this->assertStringContainsString('/Type /Sig', $content);
        $this->assertStringContainsString('/Filter /Adobe.PPKLite', $content);
        $this->assertStringContainsString('/SubFilter /ETSI.CAdES.detached', $content);
        $this->assertStringContainsString('/Contents <', $content);
        $this->assertStringContainsString('/ByteRange [**********', $content);
        $this->assertStringContainsString('/Extensions <<', $content);
        $this->assertStringContainsString('/ESIC <<', $content);
        $this->assertStringNotContainsString('/Prop_Build', $content);
    }

    public function test_it_reserves_space_for_large_timestamped_cms_signatures(): void
    {
        $input = __DIR__ . '/Output/minimal-large-placeholder.pdf';
        $output = __DIR__ . '/Output/minimal-large-placeholder-signed.pdf';

        if (! is_dir(dirname($input))) {
            mkdir(dirname($input), 0777, true);
        }

        (new MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign($input, $output);

        $content = file_get_contents($output);

        $this->assertNotFalse($content);

        preg_match(
            '/\/Contents\s*<([0-9A-F]+)>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertGreaterThanOrEqual(
            131072,
            strlen($matches[1])
        );
    }

    public function test_it_fills_signature_placeholders_when_certificate_is_provided(): void
    {
        $input = __DIR__ . '/Output/minimal.pdf';
        $output = __DIR__ . '/Output/minimal-real-signed.pdf';

        if (! is_dir(dirname($input))) {
            mkdir(dirname($input), 0777, true);
        }

        (new MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456'
        );

        $this->assertFileExists($output);

        $content = file_get_contents($output);

        $this->assertStringContainsString('/Type /Sig', $content);
        $this->assertStringContainsString('/SubFilter /ETSI.CAdES.detached', $content);
        $this->assertStringContainsString('/ByteRange [0 ', $content);
        $this->assertStringNotContainsString('/ByteRange [**********', $content);
        $this->assertStringContainsString('/Contents <3082', $content);
        $this->assertStringContainsString('/Subtype /Widget', $content);
        $this->assertStringContainsString('/FT /Sig', $content);
        $this->assertStringContainsString('/Fields [', $content);
        $this->assertStringContainsString('/AcroForm ', $content);
        $this->assertStringContainsString('/ESIC <<', $content);
        $this->assertStringNotContainsString('/Prop_Build', $content);
        $this->assertStringContainsString('/Annots [', $content);
        $this->assertStringContainsString('/Subtype /Widget', $content);
        $this->assertStringContainsString('/P ', $content);
        $this->assertStringContainsString('/M (D:', $content);

        $this->assertStringContainsString('/Name (PAdES Core)', $content);

        $this->assertStringContainsString(
            '/Reason (Document signed digitally)',
            $content
        );
    }

    public function test_it_generates_visible_signature_field_when_requested(): void
    {
        $input = __DIR__ . '/Output/minimal-visible-input.pdf';
        $output = __DIR__ . '/Output/minimal-visible-output.pdf';

        if (! is_dir(dirname($input))) {
            mkdir(dirname($input), 0777, true);
        }

        (new MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            signatureName: 'Admin User',
            signatureReason: 'Assinatura digital de documento assistencial',
            signatureLocation: 'Prontuario Eletronico MPTO',
            signatureContactInfo: 'admin@adm.com'
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('/Rect [48 48 547 96]', $content);
        $this->assertStringContainsString('/F 132', $content);
        $this->assertStringContainsString('/AP <<', $content);
        $this->assertStringContainsString('/Type /XObject', $content);
        $this->assertStringContainsString('/Subtype /Form', $content);
        $this->assertStringContainsString('/BBox [0 0 499 48]', $content);
        $this->assertStringContainsString('/Name (Admin User)', $content);
        $this->assertStringContainsString(
            '/Reason (Assinatura digital de documento assistencial)',
            $content
        );
        $this->assertStringContainsString(
            '/Location (Prontuario Eletronico MPTO)',
            $content
        );
        $this->assertStringContainsString('/ContactInfo (admin@adm.com)', $content);
        $this->assertStringContainsString('/ETSI.CAdES.detached', $content);
        $this->assertStringContainsString('/ESIC <<', $content);
    }

    public function test_it_signs_existing_empty_named_signature_field(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-empty-field-');
        $output = tempnam(sys_get_temp_dir(), 'pades-filled-field-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdfWithEmptySignatureField());

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            signatureFieldName: 'Approval'
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertSame(2, preg_match_all('/\/Subtype\s*\/Widget\b/', $content));
        $this->assertStringContainsString('/T (Approval)', $content);
        $this->assertMatchesRegularExpression('/4\s+0\s+obj\s*<<.*\/V\s+6\s+0\s+R.*>>\s*endobj/s', $content);
        $this->assertDoesNotMatchRegularExpression('/7\s+0\s+obj\s*<<.*\/Subtype\s*\/Widget\b/s', $content);
    }

    public function test_real_pdf_signer_generates_pades_b_b_ready_cms(): void
    {
        $input = __DIR__ . '/Output/pades-bb-input.pdf';

        $output = __DIR__ . '/Output/pades-bb-output.pdf';

        (new \NihilLabs\Pades\Pdf\MinimalPdfGenerator())
            ->generate($input);

        (new \NihilLabs\Pades\Pdf\RealPdfSigner())
            ->sign(
                inputPdf: $input,
                outputPdf: $output,
                certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
                certificatePassword: '123456'
            );

        $pdf = file_get_contents($output);

        $this->assertNotFalse($pdf);

        $cms = (new \NihilLabs\Pades\Pdf\PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $inspection = (new \NihilLabs\Pades\Validation\PadesBaselineInspector())
            ->inspect($cms);

        $this->assertTrue(
            $inspection['has_signing_certificate_v2']
        );

        $this->assertTrue(
            $inspection['is_pades_b_b_ready']
        );
    }

    private function buildPdfWithEmptySignatureField(): string
    {
        return $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R /AcroForm 5 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /Annots [4 0 R] /MediaBox [0 0 612 792] >>",
            4 => "<< /Type /Annot /Subtype /Widget /FT /Sig /Rect [0 0 0 0] /T (Approval) /F 4 /P 3 0 R >>",
            5 => "<< /Fields [4 0 R] /SigFlags 3 >>",
        ]);
    }

    /**
     * @param array<int, string> $objects
     */
    private function buildPdf(array $objects): string
    {
        $pdf = "%PDF-1.7\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($number = 1; $number < $size; $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number] ?? 0);
        }

        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }
}
