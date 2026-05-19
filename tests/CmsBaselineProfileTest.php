<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\CmsBaselineProfile;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class CmsBaselineProfileTest extends TestCase
{
    public function test_it_detects_signed_data_oid_in_pdf_signature(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'cms-baseline-signed-data'
        );

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $profile = new CmsBaselineProfile();

        $this->assertTrue(
            $profile->hasSignedDataOid($cms)
        );
    }

    public function test_it_inspects_baseline_profile(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'cms-baseline-profile'
        );

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

    public function test_current_pdf_signature_includes_signing_certificate_v2(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'cms-baseline-signing-certificate-v2'
        );

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $profile = new CmsBaselineProfile();

        $this->assertTrue(
            $profile->hasSigningCertificateV2Oid($cms)
        );
    }
}
