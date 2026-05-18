<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\PadesBaselineInspector;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use PHPUnit\Framework\TestCase;

final class PadesBaselineInspectorTest extends TestCase
{
    public function test_it_inspects_current_pades_baseline_status(): void
    {
        $pdf = file_get_contents(
            __DIR__ . '/Output/debug-signed.pdf'
        );

        $this->assertNotFalse($pdf);

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $inspection = (new PadesBaselineInspector())
            ->inspect($cms);

        $this->assertTrue(
            $inspection['is_cms_signed_data']
        );

        $this->assertFalse(
            $inspection['has_signing_certificate_v2']
        );

        $this->assertFalse(
            $inspection['is_pades_b_b_ready']
        );
    }
}