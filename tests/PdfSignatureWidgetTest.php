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

    public function test_it_builds_visible_signature_widget_with_appearance(): void
    {
        $widget = (new PdfSignatureWidget())
            ->build(
                signatureObjectNumber: 10,
                pageObjectNumber: 3,
                rect: [48, 48, 547, 96],
                flags: 132,
                appearanceObjectNumber: 11
            );

        $this->assertStringContainsString('/Rect [48 48 547 96]', $widget);
        $this->assertStringContainsString('/F 132', $widget);
        $this->assertStringContainsString('/AP <<', $widget);
        $this->assertStringContainsString('/N 11 0 R', $widget);
    }

    public function test_it_builds_named_signature_widget(): void
    {
        $widget = (new PdfSignatureWidget())
            ->build(
                signatureObjectNumber: 10,
                fieldName: 'Approval.Signature'
            );

        $this->assertStringContainsString('/T (Approval.Signature)', $widget);
    }
}
