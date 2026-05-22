<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Pdf\Dss\PdfDssInspector;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use RuntimeException;

final readonly class PadesLtValidator
{
    public function __construct(
        private PadesBtValidator $btValidator = new PadesBtValidator(),
        private PdfDssInspector $dssInspector = new PdfDssInspector()
    ) {}

    public function validatePdf(string $pdfContent): PadesProfileValidationResult
    {
        $bt = $this->btValidator->validatePdf($pdfContent);
        $expectedVriHash = null;

        try {
            $signature = (new PdfSignatureExtractor())->extractBinarySignatureWithoutPadding($pdfContent);
            $expectedVriHash = strtoupper(hash('sha1', $signature));
        } catch (RuntimeException) {
        }

        $dss = $this->dssInspector->inspect($pdfContent, $expectedVriHash);
        $checks = [
            ...$bt->checks,
            'dss_dictionary' => (bool) $dss['has_dss_dictionary'],
            'dss_catalog_reference' => (bool) $dss['has_dss_reference'],
            'vri_dictionary' => (bool) $dss['has_vri_dictionary'],
            'vri_hash_matches_signature' => (bool) $dss['has_expected_vri_hash'],
            'dss_certificate_chain' => (bool) $dss['has_certificates'],
            'dss_ocsp_responses' => (bool) $dss['has_ocsp_responses'],
            'dss_crls' => (bool) $dss['has_crls'],
            'offline_validation_ready' => (bool) $dss['offline_validation_ready'],
        ];
        $messages = $bt->messages;

        foreach ($checks as $name => $passed) {
            if (! $passed && ! str_starts_with($name, 'signature_') && ! str_starts_with($name, 'rfc3161_')) {
                $messages[] = "Requisito PAdES-B-LT ausente: {$name}.";
            }
        }

        return new PadesProfileValidationResult(
            profile: PadesBaselineProfile::B_LT,
            valid: $bt->valid
                && (bool) $dss['complete_lt_material']
                && (bool) $dss['has_expected_vri_hash']
                && $messages === [],
            checks: $checks,
            messages: $messages
        );
    }
}
