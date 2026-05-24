<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ManualReaderCompatibilityTest extends TestCase
{
    public function test_adobe_acrobat_manual_validation_is_recorded(): void
    {
        $pdf = $this->visibleSignaturePdf('adobe-acrobat-visible.pdf');
        $this->requireManualAcceptance(
            tool: 'Adobe Acrobat',
            environmentVariable: 'ADOBE_ACROBAT_ACCEPTED_SHA256',
            pdf: $pdf,
            criteria: [
                'abre sem reparo do arquivo',
                'mostra painel de assinatura digital',
                'aparencia visivel aparece na pagina final',
                'nao acusa fonte da assinatura como nao incorporada',
            ]
        );
    }

    public function test_adobe_reader_manual_validation_is_recorded(): void
    {
        $pdf = $this->visibleSignaturePdf('adobe-reader-visible.pdf');
        $this->requireManualAcceptance(
            tool: 'Adobe Reader',
            environmentVariable: 'ADOBE_READER_ACCEPTED_SHA256',
            pdf: $pdf,
            criteria: [
                'abre sem reparo do arquivo',
                'mostra campo de assinatura',
                'mostra assinatura como assinatura digital',
                'aparencia visivel esta legivel',
            ]
        );
    }

    public function test_windows_preview_manual_compatibility_is_recorded(): void
    {
        $pdf = $this->visibleSignaturePdf('windows-preview-visible.pdf');
        $this->requireManualAcceptance(
            tool: 'Windows Preview',
            environmentVariable: 'WINDOWS_PREVIEW_ACCEPTED_SHA256',
            pdf: $pdf,
            criteria: [
                'abre sem corromper',
                'renderiza a pagina assinada',
                'renderiza a pagina final de assinatura',
            ]
        );
    }

    public function test_macos_preview_manual_compatibility_is_recorded(): void
    {
        $pdf = $this->visibleSignaturePdf('macos-preview-visible.pdf');
        $this->requireManualAcceptance(
            tool: 'macOS Preview',
            environmentVariable: 'MACOS_PREVIEW_ACCEPTED_SHA256',
            pdf: $pdf,
            criteria: [
                'abre sem corromper',
                'renderiza a pagina assinada',
                'renderiza a pagina final de assinatura',
            ]
        );
    }

    public function test_browser_pdf_readers_manual_compatibility_is_recorded(): void
    {
        $pdf = $this->visibleSignaturePdf('browser-readers-visible.pdf');
        $this->requireManualAcceptance(
            tool: 'Chrome e Firefox PDF viewers',
            environmentVariable: 'BROWSER_READERS_ACCEPTED_SHA256',
            pdf: $pdf,
            criteria: [
                'Chrome renderiza a assinatura visivel',
                'Firefox renderiza a assinatura visivel',
                'ambos preservam layout e pagina final',
            ]
        );
    }

    private function visibleSignaturePdf(string $fileName): string
    {
        $directory = __DIR__ . '/Output/interoperability/manual';

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $input = $directory . '/input.pdf';
        $output = $directory . '/' . $fileName;

        if (! is_file($input)) {
            (new MinimalPdfGenerator())->generate($input);
        }

        if (! is_file($output)) {
            (new RealPdfSigner())->sign(
                inputPdf: $input,
                outputPdf: $output,
                certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
                certificatePassword: $this->certificatePassword(),
                visibleSignature: true,
                signatureName: 'PAdES Interoperability',
                signatureReason: 'Manual reader compatibility validation',
                signatureLocation: 'Interoperability Lab',
                signatureContactInfo: 'interop@example.test',
                appendSignaturePage: true
            );
        }

        return $output;
    }

    private function certificatePassword(): string
    {
        $password = getenv('PADES_INTEROP_PFX_PASSWORD');

        if (! is_string($password) || $password === '') {
            $this->markTestSkipped('Configure PADES_INTEROP_PFX_PASSWORD com a senha local do certificate.pfx.');
        }

        return $password;
    }

    /**
     * @param array<string> $criteria
     */
    private function requireManualAcceptance(
        string $tool,
        string $environmentVariable,
        string $pdf,
        array $criteria
    ): void {
        $hash = hash_file('sha256', $pdf);

        if (! is_string($hash)) {
            throw new RuntimeException("Nao foi possivel calcular SHA-256 de {$pdf}.");
        }

        $acceptedHash = getenv($environmentVariable);

        if (! is_string($acceptedHash) || strtoupper($acceptedHash) !== strtoupper($hash)) {
            $this->markTestSkipped(
                $tool . ' ainda nao foi validado manualmente para ' . $pdf
                . '. SHA-256 esperado em ' . $environmentVariable . ': ' . strtoupper($hash)
                . '. Criterios: ' . implode('; ', $criteria) . '.'
            );
        }

        $this->assertFileExists($pdf);
        $this->assertSame(64, strlen($hash));
    }
}
