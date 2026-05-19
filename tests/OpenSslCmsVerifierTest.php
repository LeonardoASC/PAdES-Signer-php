<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\OpenSslCmsVerifier;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class OpenSslCmsVerifierTest extends TestCase
{
    public function test_it_verifies_real_pdf_signature(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'openssl-cms-verifier'
        );

        $parts = SignedPdfFixture::detachedCmsParts($pdf);

        $isValid = (new OpenSslCmsVerifier())
            ->verifyDetachedSignature(
                signedData: $parts['signedData'],
                binarySignature: $parts['cms']
            );

        $this->assertIsBool($isValid);
    }
}
