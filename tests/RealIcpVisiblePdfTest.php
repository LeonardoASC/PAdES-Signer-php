<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class RealIcpVisiblePdfTest extends TestCase
{
    public function test_it_signs_real_icp_pdf_with_pyhanko_compatible_visible_field(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $input = __DIR__ . '/Fixtures/input-real.pdf';

        if (! file_exists($input)) {
            $input = RealIcpSignedPdfFixture::outputPath('icp-visible-input.pdf');
            (new MinimalPdfGenerator())->generate($input);
        }

        $output = RealIcpSignedPdfFixture::outputPath('icp-visible-output.pdf');

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: RealIcpSignedPdfFixture::certificatePath(),
            certificatePassword: RealIcpSignedPdfFixture::certificatePassword(),
            timestampClient: null,
            visibleSignature: true,
            signatureName: 'Admin User',
            signatureReason: 'Assinatura digital de documento assistencial',
            signatureLocation: 'Prontuario Eletronico MPTO',
            signatureContactInfo: 'admin@adm.com'
        );

        $pdf = file_get_contents($output);

        $this->assertNotFalse($pdf);
        $this->assertStringContainsString('/ByteRange', $pdf);
        $this->assertStringContainsString('/Filter /Adobe.PPKLite', $pdf);
        $this->assertStringContainsString('/SubFilter /ETSI.CAdES.detached', $pdf);
        $this->assertStringContainsString('/Extensions <<', $pdf);
        $this->assertStringContainsString('/ESIC <<', $pdf);

        $this->assertStringContainsString('/Rect [48 48 547 96]', $pdf);
        $this->assertStringContainsString('/F 132', $pdf);
        $this->assertStringContainsString('/AP <<', $pdf);
        $this->assertMatchesRegularExpression('/\/N\s+\d+\s+0\s+R/', $pdf);
        $this->assertStringContainsString('/Type /XObject', $pdf);
        $this->assertStringContainsString('/Subtype /Form', $pdf);
        $this->assertStringContainsString('/BBox [0 0 499 48]', $pdf);

        $this->assertStringContainsString('/Name (Admin User)', $pdf);
        $this->assertStringContainsString(
            '/Reason (Assinatura digital de documento assistencial)',
            $pdf
        );
        $this->assertStringContainsString(
            '/Location (Prontuario Eletronico MPTO)',
            $pdf
        );
        $this->assertStringContainsString('/ContactInfo (admin@adm.com)', $pdf);
        $this->assertStringNotContainsString('/Prop_Build', $pdf);
    }
}
