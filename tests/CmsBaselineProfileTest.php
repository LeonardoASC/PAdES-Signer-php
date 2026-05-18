<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\CmsBaselineProfile;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use PHPUnit\Framework\TestCase;

final class CmsBaselineProfileTest extends TestCase
{
    public function test_it_detects_signed_data_oid_in_pdf_signature(): void
    {
        $pdf = file_get_contents(
            __DIR__ . '/Output/debug-signed.pdf'
        );

        $this->assertNotFalse($pdf);

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $profile = new CmsBaselineProfile();

        $this->assertTrue(
            $profile->hasSignedDataOid($cms)
        );
    }

    public function test_it_inspects_baseline_profile(): void
    {
        $pdf = file_get_contents(
            __DIR__ . '/Output/debug-signed.pdf'
        );

        $this->assertNotFalse($pdf);

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $inspection = (new CmsBaselineProfile())
            ->inspect($cms);

        $this->assertArrayHasKey(
            'has_signed_data_oid',
            $inspection
        );

        $this->assertArrayHasKey(
            'has_signing_certificate_v2_oid',
            $inspection
        );

        $this->assertTrue(
            $inspection['has_signed_data_oid']
        );
    }

    public function test_current_openssl_cms_does_not_include_signing_certificate_v2_yet(): void
    {
        $pdf = file_get_contents(
            __DIR__ . '/Output/debug-signed.pdf'
        );

        $this->assertNotFalse($pdf);

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $profile = new CmsBaselineProfile();

        $this->assertFalse(
            $profile->hasSigningCertificateV2Oid($cms)
        );
    }
}
