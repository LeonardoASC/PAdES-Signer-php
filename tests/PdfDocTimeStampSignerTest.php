<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Pdf\PdfDocTimeStampSigner;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use PHPUnit\Framework\TestCase;

final class PdfDocTimeStampSignerTest extends TestCase
{
    public function test_it_adds_pdf_document_timestamp_signature(): void
    {
        $response = file_get_contents(__DIR__ . '/Fixtures/timestamp-response.tsr');
        $this->assertNotFalse($response);

        $client = new class($response) implements TimestampClientInterface {
            public function __construct(
                private readonly string $response
            ) {}

            public function requestToken(string $timestampRequestDer): string
            {
                return $this->response;
            }
        };

        $input = __DIR__ . '/Fixtures/sample.pdf';
        $output = tempnam(sys_get_temp_dir(), 'pades-doc-timestamp-');
        $this->assertIsString($output);

        (new PdfDocTimeStampSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            timestampClient: $client,
            signatureFieldName: 'DocTimeStamp1'
        );

        $pdf = file_get_contents($output);

        $this->assertNotFalse($pdf);
        $this->assertStringContainsString('/SubFilter /ETSI.RFC3161', $pdf);
        $this->assertStringContainsString('/ByteRange [0 ', $pdf);
        $this->assertStringContainsString('/T (DocTimeStamp1)', $pdf);

        $token = (new PdfSignatureExtractor())->extractBinarySignatureWithoutPadding($pdf);

        $this->assertStringStartsWith("\x30", $token);
    }
}
