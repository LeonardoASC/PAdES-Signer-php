<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfSignatureAppearance;
use PHPUnit\Framework\TestCase;

final class PdfSignatureAppearanceTest extends TestCase
{
    public function test_it_builds_form_xobject_appearance_stream(): void
    {
        $appearance = (new PdfSignatureAppearance())
            ->build('Digitally signed by Admin User');

        $this->assertStringContainsString('/Type /XObject', $appearance);
        $this->assertStringContainsString('/Subtype /Form', $appearance);
        $this->assertStringContainsString('/BBox [0 0 499 48]', $appearance);
        $this->assertStringContainsString('stream', $appearance);
        $this->assertStringNotContainsString('/Font', $appearance);
        $this->assertStringNotContainsString('/BaseFont', $appearance);
        $this->assertStringContainsString(' re f', $appearance);
        $this->assertStringNotContainsString('Admin User', $appearance);
    }

    public function test_it_scales_the_appearance_to_the_signature_rectangle(): void
    {
        $appearance = (new PdfSignatureAppearance())->build(
            "ASSINATURA DIGITAL\nAssinado por: Admin User\nData: 23/05/2026 10:20:30 -0300\nMotivo: Teste\nLocal: Prontuario",
            width: 499,
            height: 580
        );

        $this->assertStringContainsString('/BBox [0 0 499 580]', $appearance);
        $this->assertStringNotContainsString('/Font', $appearance);
        $this->assertGreaterThan(120, substr_count($appearance, ' re f'));
    }
}
