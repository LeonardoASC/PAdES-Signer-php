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
        $this->assertStringContainsString('/SubFilter /adbe.pkcs7.detached', $content);
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

        file_put_contents(
            __DIR__ . '/Output/debug-signed.pdf',
            $content
        );

        $this->assertStringContainsString('/Type /Sig', $content);
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
}
