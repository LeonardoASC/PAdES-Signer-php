<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfAcroForm;
use PHPUnit\Framework\TestCase;

final class PdfAcroFormTest extends TestCase
{
    public function test_it_builds_acroform_with_signature_field(): void
    {
        $acroForm = (new PdfAcroForm())
            ->build(widgetObjectNumber: 11);

        $this->assertStringContainsString('/Fields [11 0 R]', $acroForm);

        $this->assertStringContainsString('/SigFlags 3', $acroForm);
    }
}