<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Validation\PadesBaselineProfile;
use NihilLabs\Pades\Validation\PadesBbValidator;
use PHPUnit\Framework\TestCase;

final class PadesBbValidatorTest extends TestCase
{
    public function test_it_validates_complete_pades_b_b_pdf(): void
    {
        $pdf = $this->signedPdf();

        $result = (new PadesBbValidator())->validatePdf($pdf);

        $this->assertSame(PadesBaselineProfile::B_B, $result->profile);
        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertTrue($result->checks['pdf_signature_dictionary']);
        $this->assertTrue($result->checks['etsi_cades_detached_subfilter']);
        $this->assertTrue($result->checks['valid_byte_range']);
        $this->assertTrue($result->checks['cms_signed_data']);
        $this->assertTrue($result->checks['content_type_attribute']);
        $this->assertTrue($result->checks['message_digest_attribute']);
        $this->assertTrue($result->checks['signing_certificate_v2_attribute']);
    }

    public function test_it_reports_missing_pades_b_b_requirements(): void
    {
        $result = (new PadesBbValidator())->validatePdf('%PDF-1.4 fake');

        $this->assertFalse($result->valid);
        $this->assertFalse($result->checks['pdf_signature_dictionary']);
        $this->assertNotEmpty($result->messages);
    }

    private function signedPdf(): string
    {
        $output = tempnam(sys_get_temp_dir(), 'pades-bb-');
        $this->assertIsString($output);

        (new RealPdfSigner())->sign(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456'
        );

        $pdf = file_get_contents($output);
        $this->assertNotFalse($pdf);

        return $pdf;
    }
}
