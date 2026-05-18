<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\OpenSslCmsVerifier;
use NihilLabs\Pades\Pdf\ByteRange;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use PHPUnit\Framework\TestCase;

final class OpenSslCmsVerifierTest extends TestCase
{
    public function test_it_verifies_real_pdf_signature(): void
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
            ->extractSignedData(
                $pdf,
                $byteRange
            );

        $signature = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $isValid = (new OpenSslCmsVerifier())
            ->verifyDetachedSignature(
                signedData: $signedData,
                binarySignature: $signature
            );

        $this->assertIsBool($isValid);
    }
}