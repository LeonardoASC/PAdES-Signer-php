<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use PHPUnit\Framework\TestCase;

final class RealIcpCertificatePdfTest extends TestCase
{
    public function test_it_signs_pdf_with_real_icp_certificate(): void
    {
        $certificatePath = __DIR__ . '/Fixtures/icp-valid.pfx';
        $certificatePassword = getenv('ICP_PFX_PASSWORD');

        if (! file_exists($certificatePath) || $certificatePassword === false) {
            $this->markTestSkipped('Certificado ICP ou senha não configurados.');
        }

        $input = __DIR__ . '/Output/icp-input.pdf';
        $output = __DIR__ . '/Output/icp-signed-output.pdf';

        @unlink($output);

        (new MinimalPdfGenerator())
            ->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: $certificatePath,
            certificatePassword: $certificatePassword
        );

        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
    }
}