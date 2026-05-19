<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\OpenSslBinaryCmsVerifier;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class RealIcpTimestampedOpenSslVerificationTest extends TestCase
{
    public function test_it_verifies_real_icp_timestamped_pdf_with_openssl(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $pdf = RealIcpSignedPdfFixture::timestampedPdfContent();
        $parts = SignedPdfFixture::detachedCmsParts($pdf);

        $this->assertStringContainsString(
            RealIcpSignedPdfFixture::TIMESTAMP_TOKEN_OID_HEX,
            strtoupper(bin2hex($parts['cms']))
        );

        $this->assertTrue(
            (new OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $parts['cms'],
                    signedData: $parts['signedData']
                )
        );
    }
}
