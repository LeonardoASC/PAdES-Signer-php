<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\X509\CertificateFormatNormalizer;
use NihilLabs\Pades\Pdf\Dss\PdfDssInspector;
use NihilLabs\Pades\Pdf\PdfLtvEnricher;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Validation\PadesBaselineProfile;
use NihilLabs\Pades\Validation\PadesLtValidator;
use PHPUnit\Framework\TestCase;

final class PadesLtValidatorTest extends TestCase
{
    public function test_it_validates_complete_pades_b_lt_pdf(): void
    {
        $signedPdf = $this->timestampedPdf();
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );
        $certificateDer = (new CertificateFormatNormalizer())
            ->normalizeToDer($certificate->getPublicCertificate());

        $enrichedPdf = (new PdfLtvEnricher())->enrich(
            signedPdfContent: $signedPdf,
            material: new LtvValidationMaterial(
                certificatesDer: [$certificateDer],
                ocspResponsesDer: ["\x30\x03\x0A\x01\x00"],
                crlsDer: ["\x30\x03\x0A\x01\x01"]
            )
        );

        $result = (new PadesLtValidator())->validatePdf($enrichedPdf);

        $this->assertSame(PadesBaselineProfile::B_LT, $result->profile);
        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertTrue($result->checks['dss_dictionary']);
        $this->assertTrue($result->checks['vri_dictionary']);
        $this->assertTrue($result->checks['vri_hash_matches_signature']);
        $this->assertTrue($result->checks['dss_certificate_chain']);
        $this->assertTrue($result->checks['dss_ocsp_responses']);
        $this->assertTrue($result->checks['dss_crls']);
        $this->assertTrue($result->checks['offline_validation_ready']);
    }

    public function test_it_rejects_b_t_pdf_without_dss(): void
    {
        $result = (new PadesLtValidator())->validatePdf($this->timestampedPdf());

        $this->assertFalse($result->valid);
        $this->assertFalse($result->checks['dss_dictionary']);
    }

    public function test_dss_inspector_reports_embedded_validation_material(): void
    {
        $pdf = "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /DSS 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /DSS /Certs [3 0 R] /OCSPs [4 0 R] /CRLs [5 0 R] /VRI << /ABCDEF0123456789ABCDEF0123456789ABCDEF01 << /Cert [3 0 R] /OCSP [4 0 R] /CRL [5 0 R] >> >> >>\nendobj\n";

        $inspection = (new PdfDssInspector())->inspect(
            $pdf,
            'ABCDEF0123456789ABCDEF0123456789ABCDEF01'
        );

        $this->assertTrue($inspection['has_dss_reference']);
        $this->assertTrue($inspection['has_dss_dictionary']);
        $this->assertTrue($inspection['has_vri_dictionary']);
        $this->assertTrue($inspection['has_certificates']);
        $this->assertTrue($inspection['has_ocsp_responses']);
        $this->assertTrue($inspection['has_crls']);
        $this->assertTrue($inspection['has_expected_vri_hash']);
    }

    private function timestampedPdf(): string
    {
        $response = file_get_contents(__DIR__ . '/Fixtures/timestamp-response.tsr');
        $this->assertNotFalse($response);

        $client = new class($response) implements TimestampClientInterface {
            public function __construct(private readonly string $response) {}

            public function requestToken(string $timestampRequestDer): string
            {
                return $this->response;
            }
        };

        $output = tempnam(sys_get_temp_dir(), 'pades-lt-');
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
