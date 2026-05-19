<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\OpenSslBinaryCmsVerifier;
use NihilLabs\Pades\Pdf\ByteRange;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use PHPUnit\Framework\TestCase;

final class OpenSslBinaryCmsVerifierTest extends TestCase
{
    public function test_it_verifies_pdf_cms_signature_with_openssl_binary_mode(): void
    {
        $pdf = file_get_contents(
            __DIR__ . '/Output/debug-signed.pdf'
        );

        $this->assertNotFalse($pdf);

        preg_match(
            '/\/ByteRange\s*\[(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\]/',
            $pdf,
            $matches
        );

        $byteRange = new ByteRange(
            start1: (int) $matches[1],
            length1: (int) $matches[2],
            start2: (int) $matches[3],
            length2: (int) $matches[4]
        );

        $signedData = (new ByteRangeCalculator())
            ->extractSignedData($pdf, $byteRange);

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $this->assertTrue(
            (new OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $cms,
                    signedData: $signedData
                )
        );
    }
}