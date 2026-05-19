<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use PHPUnit\Framework\TestCase;

final class RealPdfSignerTimestampTest extends TestCase
{
    public function test_it_signs_pdf_with_timestamp_token(): void
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

        $input = __DIR__ . '/Output/timestamped-input.pdf';

        $output = __DIR__ . '/Output/timestamped-output.pdf';

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

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $this->assertStringContainsString(
            '2a864886f70d010910020e',
            bin2hex($cms)
        );
    }
}
