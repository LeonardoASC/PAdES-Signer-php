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
        $this->assertStringContainsString('/BBox [ 0 48 499 0 ]', $appearance);
        $this->assertStringContainsString('stream', $appearance);
        $this->assertStringContainsString('Digitally signed by Admin User', $appearance);
    }
}
