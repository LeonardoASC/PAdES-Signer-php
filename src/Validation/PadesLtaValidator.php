<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampValidator;
use NihilLabs\Pades\Pdf\PdfDocTimeStampInspector;

final readonly class PadesLtaValidator
{
    public function __construct(
        private PadesLtValidator $ltValidator = new PadesLtValidator(),
        private PdfDocTimeStampInspector $docTimeStampInspector = new PdfDocTimeStampInspector(),
        private Rfc3161TimestampValidator $timestampValidator = new Rfc3161TimestampValidator()
    ) {}

    public function validatePdf(string $pdfContent): PadesProfileValidationResult
    {
        $lt = $this->ltValidator->validatePdf($pdfContent);
        $checks = [
            ...$lt->checks,
            'document_timestamp' => $this->docTimeStampInspector->hasDocumentTimestamp($pdfContent),
            'archival_timestamp_after_dss' => $this->docTimeStampInspector->isLatestRevisionAfterDss($pdfContent),
            'archival_timestamp_token_valid' => false,
        ];
        $messages = $lt->messages;

        if ($checks['document_timestamp']) {
            $timestamp = $this->timestampValidator->validateToken(
                $this->docTimeStampInspector->extractLatestDocumentTimestampToken($pdfContent)
            );
            $checks['archival_timestamp_token_valid'] = $timestamp->valid;

            if (! $timestamp->valid) {
                array_push($messages, ...$timestamp->messages);
            }
        } else {
            $messages[] = 'Requisito PAdES-B-LTA ausente: document_timestamp.';
        }

        if (! $checks['archival_timestamp_after_dss']) {
            $messages[] = 'Requisito PAdES-B-LTA ausente: archival_timestamp_after_dss.';
        }

        return new PadesProfileValidationResult(
            profile: PadesBaselineProfile::B_LTA,
            valid: $lt->valid
                && $checks['document_timestamp']
                && $checks['archival_timestamp_after_dss']
                && $checks['archival_timestamp_token_valid']
                && $messages === [],
            checks: $checks,
            messages: $messages
        );
    }
}
