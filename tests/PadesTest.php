<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pades;
use NihilLabs\Pades\PadesSignatureOptions;
use NihilLabs\Pades\PadesSigner;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use PHPUnit\Framework\TestCase;

final class PadesTest extends TestCase
{
    public function test_it_signs_pdf_using_static_api(): void
    {
        $input = __DIR__ . '/Output/pades-api-input.pdf';

        $output = __DIR__ . '/Output/pades-api-output.pdf';

        (new MinimalPdfGenerator())
            ->generate($input);

        Pades::sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456'
        );

        $this->assertFileExists($output);

        $content = file_get_contents($output);

        $this->assertNotFalse($content);

        $this->assertStringContainsString(
            '/Type /Sig',
            $content
        );
    }

    public function test_it_signs_pdf_using_public_pades_signer_api(): void
    {
        $input = __DIR__ . '/Fixtures/sample.pdf';

        $output = __DIR__ . '/Output/pades-api-output.pdf';

        $credential = new PfxSignatureCredential(
            pathOrCertificate: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        (new PadesSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            credential: $credential,
            options: new PadesSignatureOptions(
                signatureName: 'Public PAdES API'
            )
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('/Type /Sig', $content);
        $this->assertStringContainsString('/Name (Public PAdES API)', $content);
        $this->assertStringContainsString('/ByteRange [0 ', $content);
    }
}
