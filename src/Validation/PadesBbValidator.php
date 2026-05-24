<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use RuntimeException;

final readonly class PadesBbValidator
{
    public function validatePdf(string $pdfContent): PadesProfileValidationResult
    {
        $cms = null;
        $pdfSignatureReport = null;
        $cmsValidation = null;

        try {
            $pdfSignatureReport = (new PdfSignatureValidator())->validateFirst($pdfContent);
            $cms = $pdfSignatureReport->signature->contentsDerWithoutPadding;
            $cmsValidation = (new CmsCadesValidator())->validate(
                cmsDer: $cms,
                signedData: $pdfSignatureReport->signature->signedData
            );
        } catch (RuntimeException) {
        }

        $cmsInspection = $cms === null
            ? (new PadesBaselineInspector())->inspect('')
            : (new PadesBaselineInspector())->inspect($cms);

        $checks = [
            'pdf_signature_dictionary' => str_contains($pdfContent, '/Type /Sig'),
            'adobe_ppklite_filter' => str_contains($pdfContent, '/Filter /Adobe.PPKLite'),
            'etsi_cades_detached_subfilter' => str_contains($pdfContent, '/SubFilter /ETSI.CAdES.detached'),
            'valid_byte_range' => (bool) ($pdfSignatureReport?->checks['byte_range_semantic'] ?? false),
            'signature_contents' => $cms !== null && $cms !== '',
            'cms_signed_data' => $cmsInspection['is_cms_signed_data'],
            'content_type_attribute' => $cmsInspection['has_content_type'],
            'message_digest_attribute' => $cmsInspection['has_message_digest'],
            'message_digest_matches_pdf_bytes' => (bool) ($pdfSignatureReport?->checks['cms_message_digest_matches'] ?? false),
            'cryptographic_signature' => (bool) ($pdfSignatureReport?->checks['cryptographic_signature'] ?? false),
            'signer_certificate_present' => (bool) ($pdfSignatureReport?->checks['signer_certificate_present'] ?? false),
            'signer_certificate_matches_signer_info' => (bool) ($pdfSignatureReport?->checks['signer_certificate_matches_signer_info'] ?? false),
            'cms_cades_internal_validation' => (bool) ($cmsValidation?->valid ?? false),
            'ess_cert_id_v2_matches_signer_certificate' => (bool) ($cmsValidation?->checks['ess_cert_id_v2_matches_signer_certificate'] ?? false),
            'issuer_serial_matches_signer_certificate' => (bool) ($cmsValidation?->checks['issuer_serial_matches_signer_certificate'] ?? false),
            'cms_algorithm_policy' => (bool) (
                ($cmsValidation?->checks['digest_algorithm_policy'] ?? false)
                && ($cmsValidation?->checks['signature_algorithm_policy'] ?? false)
                && ($cmsValidation?->checks['signature_algorithm_parameters'] ?? false)
            ),
            'signing_certificate_v2_attribute' => $cmsInspection['has_signing_certificate_v2'],
        ];

        $messages = [];

        foreach ($checks as $name => $passed) {
            if (! $passed) {
                $messages[] = "Requisito PAdES-B-B ausente: {$name}.";
            }
        }

        return new PadesProfileValidationResult(
            profile: PadesBaselineProfile::B_B,
            valid: $messages === [],
            checks: $checks,
            messages: $messages
        );
    }
}
