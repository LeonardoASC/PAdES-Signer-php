<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Exception\CertificateException;
use NihilLabs\Pades\Exception\InvalidPadesArgumentException;
use NihilLabs\Pades\Exception\LtvException;
use NihilLabs\Pades\Exception\PdfReadException;
use NihilLabs\Pades\Exception\SigningException;
use NihilLabs\Pades\Exception\TrustStoreException;
use NihilLabs\Pades\Pades;
use NihilLabs\Pades\PadesLtvEnricher;
use NihilLabs\Pades\PadesSignatureOptions;
use NihilLabs\Pades\PadesSigner;
use NihilLabs\Pades\PadesTrustStore;
use NihilLabs\Pades\PadesValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PublicErrorHandlingTest extends TestCase
{
    public function test_static_api_throws_public_invalid_argument_for_missing_metadata(): void
    {
        $this->expectException(InvalidPadesArgumentException::class);
        $this->expectException(InvalidArgumentException::class);

        Pades::sign(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: __DIR__ . '/Output/error-metadata.pdf',
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: 'unused',
            options: new PadesSignatureOptions(
                signatureName: 'Signer'
            )
        );
    }

    public function test_static_api_wraps_pfx_loading_errors(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectException(RuntimeException::class);

        Pades::sign(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: __DIR__ . '/Output/error-certificate.pdf',
            certificatePath: __DIR__ . '/Fixtures/missing.pfx',
            certificatePassword: 'unused',
            options: $this->completeOptions()
        );
    }

    public function test_public_signer_wraps_pdf_signing_errors(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-invalid-pdf-');
        $output = tempnam(sys_get_temp_dir(), 'pades-invalid-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);
        $this->assertNotFalse(file_put_contents($input, 'not a pdf'));

        $this->expectException(SigningException::class);
        $this->expectException(RuntimeException::class);

        (new PadesSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            options: $this->completeOptions()
        );
    }

    public function test_public_validator_throws_pdf_read_exception_for_missing_file(): void
    {
        $this->expectException(PdfReadException::class);

        (new PadesValidator())->validateFile(__DIR__ . '/Fixtures/missing.pdf');
    }

    public function test_public_validator_throws_public_invalid_argument_for_invalid_profile(): void
    {
        $this->expectException(InvalidPadesArgumentException::class);

        (new PadesValidator())->validateProfile('%PDF-1.4', 'invalid-profile');
    }

    public function test_public_ltv_enricher_wraps_invalid_lt_content(): void
    {
        $this->expectException(LtvException::class);

        (new PadesLtvEnricher())->addLt(
            signedPdfContent: 'not a pdf',
            material: new LtvValidationMaterial()
        );
    }

    public function test_public_trust_store_throws_public_exception_for_missing_file(): void
    {
        $this->expectException(TrustStoreException::class);

        PadesTrustStore::fromFile(__DIR__ . '/Fixtures/missing-root.pem');
    }

    private function completeOptions(): PadesSignatureOptions
    {
        return new PadesSignatureOptions(
            signatureName: 'Signer',
            signatureReason: 'Digital signature',
            signatureLocation: 'Test',
            signatureContactInfo: 'signer@example.com'
        );
    }
}
