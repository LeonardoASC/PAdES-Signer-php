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
    }
}