<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class RealIcpTimestampedPdfTest extends TestCase
{
    public function test_it_signs_real_icp_pdf_with_rfc3161_timestamp(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $output = RealIcpSignedPdfFixture::timestampedPdfPath();

        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));

        $pdf = file_get_contents($output);

        $this->assertNotFalse($pdf);
        $this->assertStringContainsString('/ByteRange', $pdf);
        $this->assertStringContainsString('/ETSI.CAdES.detached', $pdf);

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $this->assertStringContainsString(
            RealIcpSignedPdfFixture::TIMESTAMP_TOKEN_OID_HEX,
            strtoupper(bin2hex($cms))
        );
    }
}
