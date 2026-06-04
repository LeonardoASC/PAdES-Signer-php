<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pades;
use NihilLabs\Pades\PadesProfile;
use NihilLabs\Pades\PadesSignatureOptions;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PadesTest extends TestCase
{
    public function test_clean_api_signs_bb_and_enriches_lt_lta(): void
    {
        $input = __DIR__ . '/Output/pades-clean-bb-input.pdf';
        $bb = __DIR__ . '/Output/pades-clean-bb.pdf';
        $lt = __DIR__ . '/Output/pades-clean-lt.pdf';
        $lta = __DIR__ . '/Output/pades-clean-lta.pdf';

        (new MinimalPdfGenerator())->generate($input);

        $credential = new PfxSignatureCredential(
            pathOrCertificate: __DIR__ . '/Fixtures/certificate.pfx',
            password: $this->certificatePassword()
        );

        $result = Pades::signBb(
            inputPdf: $input,
            outputPdf: $bb,
            credential: $credential,
            options: $this->completeOptions('PAdES B-B')
        );

        $this->assertSame(PadesProfile::B_B, $result->profile);

        Pades::addLt(
            inputPdf: $bb,
            outputPdf: $lt,
            material: new LtvValidationMaterial(
                certificatesDer: ["\x30\x01\x01"],
                ocspResponsesDer: ["\x30\x01\x02"],
                crlsDer: ["\x30\x01\x03"]
            )
        );

        $ltContent = file_get_contents($lt);
        $this->assertNotFalse($ltContent);
        $this->assertStringContainsString('/Type /DSS', $ltContent);

        Pades::addLta(
            inputPdf: $lt,
            outputPdf: $lta,
            timestampProvider: $this->timestampClient()
        );

        $ltaContent = file_get_contents($lta);
        $this->assertNotFalse($ltaContent);
        $this->assertStringContainsString('/SubFilter /ETSI.RFC3161', $ltaContent);
    }

    public function test_clean_api_signs_bt_with_timestamp_provider(): void
    {
        $input = __DIR__ . '/Output/pades-clean-bt-input.pdf';
        $bt = __DIR__ . '/Output/pades-clean-bt.pdf';

        (new MinimalPdfGenerator())->generate($input);

        $credential = new PfxSignatureCredential(
            pathOrCertificate: __DIR__ . '/Fixtures/certificate.pfx',
            password: $this->certificatePassword()
        );

        $result = Pades::signBt(
            inputPdf: $input,
            outputPdf: $bt,
            credential: $credential,
            timestampProvider: $this->timestampClient(),
            options: $this->completeOptions('PAdES B-T')
        );

        $this->assertSame(PadesProfile::B_T, $result->profile);

        $content = file_get_contents($bt);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('/ByteRange [0 ', $content);
    }

    public function test_clean_bb_api_rejects_timestamp_options(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Use Pades::signBt');

        Pades::signBb(
            inputPdf: __DIR__ . '/Fixtures/missing.pdf',
            outputPdf: __DIR__ . '/Output/pades-clean-invalid.pdf',
            credential: $this->createMock(SignatureCredentialInterface::class),
            options: new PadesSignatureOptions(
                timestampProvider: $this->timestampClient(),
                signatureName: 'Invalid B-B',
                signatureReason: 'Timestamp belongs to B-T',
                signatureLocation: 'Test',
                signatureContactInfo: 'test@example.com'
            )
        );
    }

    public function test_clean_api_requires_signature_metadata(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Pades::signBb(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: __DIR__ . '/Output/pades-missing-metadata.pdf',
            credential: $this->createMock(SignatureCredentialInterface::class),
            options: new PadesSignatureOptions(
                signatureName: 'Public PAdES API',
                signatureReason: 'Assinatura digital de teste'
            )
        );
    }

    private function certificatePassword(): string
    {
        $password = getenv('PADES_INTEROP_PFX_PASSWORD');

        if (! is_string($password) || $password === '') {
            $this->markTestSkipped('Configure PADES_INTEROP_PFX_PASSWORD para rodar testes com certificate.pfx local.');
        }

        return $password;
    }

    private function completeOptions(string $name): PadesSignatureOptions
    {
        return new PadesSignatureOptions(
            signatureName: $name,
            signatureReason: 'Assinatura digital de teste',
            signatureLocation: 'Ambiente de testes',
            signatureContactInfo: 'test@example.com'
        );
    }

    private function timestampClient(): TimestampClientInterface
    {
        $response = file_get_contents(__DIR__ . '/Fixtures/timestamp-response.tsr');

        $this->assertNotFalse($response);

        return new class($response) implements TimestampClientInterface {
            public function __construct(private readonly string $response) {}

            public function requestToken(string $timestampRequestDer): string
            {
                return $this->response;
            }
        };
    }
}
