<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Validation\PadesBaselineProfile;
use NihilLabs\Pades\Validation\PadesBtValidator;
use PHPUnit\Framework\TestCase;

final class PadesBtValidatorTest extends TestCase
{
    public function test_it_validates_complete_pades_b_t_pdf(): void
    {
        $pdf = $this->signedTimestampedPdf();

        $result = (new PadesBtValidator())->validatePdf($pdf);

        $this->assertSame(PadesBaselineProfile::B_T, $result->profile);
        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertTrue($result->checks['cms_signed_data']);
        $this->assertTrue($result->checks['signing_certificate_v2_attribute']);
        $this->assertTrue($result->checks['signature_timestamp_token']);
        $this->assertTrue($result->checks['rfc3161_timestamp_token_valid']);
    }

    public function test_it_rejects_b_b_pdf_without_signature_timestamp_token(): void
    {
        $output = tempnam(sys_get_temp_dir(), 'pades-bt-missing-');
        $this->assertIsString($output);

        (new RealPdfSigner())->sign(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456'
        );

        $pdf = file_get_contents($output);
        $this->assertNotFalse($pdf);

        $result = (new PadesBtValidator())->validatePdf($pdf);

        $this->assertFalse($result->valid);
        $this->assertFalse($result->checks['signature_timestamp_token']);
    }

    private function signedTimestampedPdf(): string
    {
        $response = file_get_contents(__DIR__ . '/Output/timestamp-response.tsr');
        $this->assertNotFalse($response);

        $client = new class($response) implements TimestampClientInterface {
            public function __construct(private readonly string $response) {}

            public function requestToken(string $timestampRequestDer): string
            {
                return $this->response;
            }
        };

        $output = tempnam(sys_get_temp_dir(), 'pades-bt-');
        $this->assertIsString($output);

        (new RealPdfSigner())->sign(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456',
            timestampClient: $client
        );

        $pdf = file_get_contents($output);
        $this->assertNotFalse($pdf);

        return $pdf;
    }
}
