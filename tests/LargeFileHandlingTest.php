<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Exception\InvalidPadesArgumentException;
use NihilLabs\Pades\Exception\PdfSizeException;
use NihilLabs\Pades\PadesLtvEnricher;
use NihilLabs\Pades\PadesSignatureOptions;
use NihilLabs\Pades\PadesSigner;
use NihilLabs\Pades\PadesValidator;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use PHPUnit\Framework\TestCase;

final class LargeFileHandlingTest extends TestCase
{
    public function test_signer_rejects_input_above_configured_size_before_loading_pdf(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-large-sign-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-large-sign-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        (new MinimalPdfGenerator())->generate($input);

        $this->expectException(PdfSizeException::class);

        (new PadesSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            credential: $this->createMock(SignatureCredentialInterface::class),
            options: new PadesSignatureOptions(maxInputPdfBytes: 10)
        );
    }

    public function test_validator_rejects_file_above_configured_size(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-large-validate-input-');

        $this->assertIsString($input);

        (new MinimalPdfGenerator())->generate($input);

        $this->expectException(PdfSizeException::class);

        PadesValidator::withMaxPdfBytes(10)->validateFile($input);
    }

    public function test_ltv_enricher_rejects_file_above_configured_size(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-large-ltv-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-large-ltv-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        (new MinimalPdfGenerator())->generate($input);

        $this->expectException(PdfSizeException::class);

        (new PadesLtvEnricher(maxPdfBytes: 10))->addLtFile(
            inputPdf: $input,
            outputPdf: $output,
            material: new LtvValidationMaterial()
        );
    }

    public function test_invalid_max_input_size_is_rejected(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-large-invalid-limit-');
        $output = tempnam(sys_get_temp_dir(), 'pades-large-invalid-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        (new MinimalPdfGenerator())->generate($input);

        $this->expectException(InvalidPadesArgumentException::class);

        (new PadesSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            credential: $this->createMock(SignatureCredentialInterface::class),
            options: new PadesSignatureOptions(maxInputPdfBytes: 0)
        );
    }
}
