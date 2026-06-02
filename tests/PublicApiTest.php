<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Credentials\PfxCredential;
use NihilLabs\Pades\PadesLtvEnricher;
use NihilLabs\Pades\PadesClient;
use NihilLabs\Pades\PadesProfile;
use NihilLabs\Pades\PadesReport;
use NihilLabs\Pades\PadesSignatureResult;
use NihilLabs\Pades\PadesSignatureOptions;
use NihilLabs\Pades\PadesSigner;
use NihilLabs\Pades\PadesValidationResult;
use NihilLabs\Pades\PadesValidator;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use PHPUnit\Framework\TestCase;

final class PublicApiTest extends TestCase
{
    public function test_public_signer_returns_signature_metadata(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-public-sign-result-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-public-sign-result-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        (new MinimalPdfGenerator())->generate($input);

        $result = (new PadesSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            options: new PadesSignatureOptions(
                visibleSignature: true,
                appendSignaturePage: true,
                signatureName: 'Public Result'
            )
        );

        $this->assertInstanceOf(PadesSignatureResult::class, $result);
        $this->assertSame($input, $result->inputPdf);
        $this->assertSame($output, $result->outputPdf);
        $this->assertSame(PadesProfile::B_B, $result->profile);
        $this->assertSame(strtoupper(hash_file('sha256', $output)), $result->sha256);
        $this->assertGreaterThan(0, $result->size);
        $this->assertTrue($result->visible);
        $this->assertTrue($result->signaturePageAppended);
        $this->assertFalse($result->timestamped);
        $this->assertSame('Public Result', $result->signatureName);
        $this->assertNotSame([], $result->warnings);
        $this->assertSame($result->sha256, $result->toArray()['sha256']);
    }

    public function test_public_signer_validator_and_report_api(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-public-api-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-public-api-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        (new MinimalPdfGenerator())->generate($input);

        (new PadesSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            credential: PfxCredential::fromFile(
                path: __DIR__ . '/Fixtures/certificate.pfx',
                password: $this->certificatePassword()
            ),
            options: new PadesSignatureOptions(
                signatureName: 'Public API',
                signatureReason: 'Public API validation'
            )
        );

        $validation = (new PadesValidator())->validateFile($output);

        $this->assertInstanceOf(PadesValidationResult::class, $validation);
        $this->assertSame(PadesProfile::B_B, $validation->profile);
        $this->assertTrue($validation->isProfile(PadesProfile::B_B));
        $this->assertTrue($validation->valid, implode("\n", $validation->messages));
        $this->assertArrayHasKey(PadesProfile::B_B, $validation->profiles);

        $report = (new PadesReport())->forFile($output);

        $this->assertSame(PadesProfile::B_B, $report['profile']);
        $this->assertTrue($report['valid']);
    }

    public function test_public_validator_can_validate_specific_profile_without_internal_api(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-public-validator-input-');

        $this->assertIsString($input);

        (new MinimalPdfGenerator())->generate($input);

        $validator = new PadesValidator();
        $result = $validator->validateProfileFile($input, PadesProfile::B_B);

        $this->assertSame(PadesProfile::B_B, $result->profile);
        $this->assertFalse($result->valid);
        $this->assertNotSame([], $result->failedChecks());

        $profiles = $validator->validateAllFile($input);

        $this->assertArrayHasKey(PadesProfile::B_B, $profiles);
        $this->assertArrayHasKey(PadesProfile::B_T, $profiles);
        $this->assertArrayHasKey(PadesProfile::B_LT, $profiles);
        $this->assertArrayHasKey(PadesProfile::B_LTA, $profiles);
        $this->assertInstanceOf(PadesValidationResult::class, $profiles[PadesProfile::B_B]);
    }

    public function test_public_client_config_signs_and_reports_without_framework_adapter(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-public-client-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-public-client-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        (new MinimalPdfGenerator())->generate($input);

        $client = PadesClient::fromConfig([
            'visible_signature' => true,
            'append_signature_page' => true,
            'signature_name' => 'Config Client',
            'signature_reason' => 'Framework agnostic usage',
            'signature_location' => 'PHP application',
            'signature_contact_info' => 'client@example.test',
        ]);

        $result = $client->sign($input, $output);

        $this->assertSame(PadesProfile::B_B, $result->profile);
        $this->assertSame('Config Client', $result->signatureName);
        $this->assertTrue($result->visible);
        $this->assertTrue($result->signaturePageAppended);

        $report = $client->reportForFile($output);

        $this->assertSame(PadesProfile::B_B, $report['profile']);
        $this->assertFalse($report['valid']);
    }

    public function test_public_ltv_enricher_api_adds_lt_material(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-public-lt-input-');
        $signed = tempnam(sys_get_temp_dir(), 'pades-public-lt-signed-');
        $lt = tempnam(sys_get_temp_dir(), 'pades-public-lt-output-');

        $this->assertIsString($input);
        $this->assertIsString($signed);
        $this->assertIsString($lt);

        (new MinimalPdfGenerator())->generate($input);

        (new PadesSigner())->sign(
            inputPdf: $input,
            outputPdf: $signed,
            credential: PfxCredential::fromFile(
                path: __DIR__ . '/Fixtures/certificate.pfx',
                password: $this->certificatePassword()
            )
        );

        (new PadesLtvEnricher())->addLtFile(
            inputPdf: $signed,
            outputPdf: $lt,
            material: new LtvValidationMaterial(
                certificatesDer: ["\x30\x01\x01"],
                ocspResponsesDer: ["\x30\x01\x02"],
                crlsDer: ["\x30\x01\x03"]
            )
        );

        $content = file_get_contents($lt);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('/Type /DSS', $content);
        $this->assertStringContainsString('/VRI <<', $content);
    }

    private function certificatePassword(): string
    {
        $password = getenv('PADES_INTEROP_PFX_PASSWORD');

        if (! is_string($password) || $password === '') {
            $this->markTestSkipped('Configure PADES_INTEROP_PFX_PASSWORD para rodar testes de API publica com certificate.pfx local.');
        }

        return $password;
    }
}
