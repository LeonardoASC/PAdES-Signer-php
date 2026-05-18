<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pades;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
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
}
