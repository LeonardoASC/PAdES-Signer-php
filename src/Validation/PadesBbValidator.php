<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Pdf\PdfByteRangeValidator;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use RuntimeException;

final readonly class PadesBbValidator
{
    public function validatePdf(string $pdfContent): PadesProfileValidationResult
    {
        $cms = null;

        try {
            $cms = (new PdfSignatureExtractor())
                ->extractBinarySignatureWithoutPadding($pdfContent);
        } catch (RuntimeException) {
        }

        $cmsInspection = $cms === null
            ? (new PadesBaselineInspector())->inspect('')
            : (new PadesBaselineInspector())->inspect($cms);

        $checks = [
            'pdf_signature_dictionary' => str_contains($pdfContent, '/Type /Sig'),
            'adobe_ppklite_filter' => str_contains($pdfContent, '/Filter /Adobe.PPKLite'),
            'etsi_cades_detached_subfilter' => str_contains($pdfContent, '/SubFilter /ETSI.CAdES.detached'),
            'valid_byte_range' => (new PdfByteRangeValidator())->validate($pdfContent),
            'signature_contents' => $cms !== null && $cms !== '',
            'cms_signed_data' => $cmsInspection['is_cms_signed_data'],
            'content_type_attribute' => $cmsInspection['has_content_type'],
            'message_digest_attribute' => $cmsInspection['has_message_digest'],
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
