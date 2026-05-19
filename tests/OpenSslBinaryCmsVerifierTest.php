<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\OpenSslBinaryCmsVerifier;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class OpenSslBinaryCmsVerifierTest extends TestCase
{
    public function test_it_verifies_pdf_cms_signature_with_openssl_binary_mode(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'openssl-binary-cms-verifier'
        );

        $parts = SignedPdfFixture::detachedCmsParts($pdf);

        $this->assertTrue(
            (new OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $parts['cms'],
                    signedData: $parts['signedData']
                )
        );
    }
}
