<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class RealIcpBasicPdfTest extends TestCase
{
    public function test_it_signs_real_icp_pdf_without_policy_timestamp_or_ltv(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $input = __DIR__ . '/Fixtures/input-real.pdf';
        $output = RealIcpSignedPdfFixture::outputPath('icp-real-input-basic-output.pdf');

        (new MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: RealIcpSignedPdfFixture::certificatePath(),
            certificatePassword: RealIcpSignedPdfFixture::certificatePassword(),
            timestampClient: null
        );

        $pdf = file_get_contents($output);

        $this->assertNotFalse($pdf);
        $this->assertStringContainsString('/ByteRange', $pdf);
        $this->assertStringContainsString('/ETSI.CAdES.detached', $pdf);

        $this->assertStringNotContainsString('/DSS', $pdf);
        $this->assertStringNotContainsString('/VRI', $pdf);
    }
}