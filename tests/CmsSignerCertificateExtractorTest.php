<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Internal\Crypto\CmsSignerCertificateExtractor;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class CmsSignerCertificateExtractorTest extends TestCase
{
    public function test_it_identifies_signer_certificate_from_signer_info(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent('cms-signer-certificate');
        $cms = (new PdfSignatureExtractor())->extractBinarySignatureWithoutPadding($pdf);

        $info = (new CmsSignerCertificateExtractor())->extract($cms);

        $this->assertTrue($info->matchesSignerInfo);
        $this->assertNotNull($info->certificateDer);
        $this->assertNotNull($info->certificatePem);
        $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $info->certificatePem);
    }
}
