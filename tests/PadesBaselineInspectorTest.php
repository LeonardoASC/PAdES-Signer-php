<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Validation\PadesBaselineInspector;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class PadesBaselineInspectorTest extends TestCase
{
    public function test_it_inspects_current_pades_baseline_status(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'pades-baseline-inspector'
        );

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $inspection = (new PadesBaselineInspector())
            ->inspect($cms);

        $this->assertTrue(
            $inspection['is_cms_signed_data']
        );

        $this->assertTrue(
            $inspection['has_signing_certificate_v2']
        );

        $this->assertTrue(
            $inspection['is_pades_b_b_ready']
        );
    }
}
