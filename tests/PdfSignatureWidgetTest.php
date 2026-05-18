<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfSignatureWidget;
use PHPUnit\Framework\TestCase;

final class PdfSignatureWidgetTest extends TestCase
{
    public function test_it_builds_signature_widget(): void
    {
        $widget = (new PdfSignatureWidget())
            ->build(
                signatureObjectNumber: 10,
                pageObjectNumber: 3
            );

        $this->assertStringContainsString('/Subtype /Widget', $widget);

        $this->assertStringContainsString('/FT /Sig', $widget);

        $this->assertStringContainsString('/V 10 0 R', $widget);

        $this->assertStringContainsString('/P 3 0 R', $widget);
    }
}