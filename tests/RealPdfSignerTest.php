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

        $inspection = (new \NihilLabs\Pades\Crypto\PadesBaselineInspector())
            ->inspect($cms);

        $this->assertTrue(
            $inspection['has_signing_certificate_v2']
        );

        $this->assertTrue(
            $inspection['is_pades_b_b_ready']
        );
    }
}
