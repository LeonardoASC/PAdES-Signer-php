<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\OpenSslBinaryCmsVerifier;
use NihilLabs\Pades\Pdf\ByteRange;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use PHPUnit\Framework\TestCase;

final class RealWorldPdfTest extends TestCase
{
    public function test_it_signs_real_world_pdf(): void
    {
        $input = __DIR__ . '/Fixtures/sample.pdf';

        $output = __DIR__ . '/Output/sample-signed.pdf';

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456'
        );

        $pdf = file_get_contents($output);

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