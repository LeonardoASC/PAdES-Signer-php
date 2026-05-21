<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Internal\Crypto\PadesCmsVerifier;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Pdf\ByteRange;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use PHPUnit\Framework\TestCase;

final class RealPdfSignerTimestampVerificationTest extends TestCase
{
    public function test_it_verifies_timestamped_pdf_signature(): void
    {
        $response = file_get_contents(
            __DIR__ . '/Output/timestamp-response.tsr'
        );

        $this->assertNotFalse($response);

        $client = new class($response) implements TimestampClientInterface {
            public function __construct(
                private readonly string $response
            ) {}

            public function requestToken(
                string $timestampRequestDer
            ): string {
                return $this->response;
            }
        };

        $input = __DIR__ . '/Output/verify-timestamp-input.pdf';

        $output = __DIR__ . '/Output/verify-timestamp-output.pdf';
        @unlink($output);

        (new MinimalPdfGenerator())
            ->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456',
            timestampClient: $client
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
            (new PadesCmsVerifier())
                ->verifyByteRangeSignature(
                    cmsDer: $cms,
                    signedData: $signedData
                )
        );
    }
}
