<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampValidationPolicy;
use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampValidator;
use NihilLabs\Pades\Internal\Crypto\Cades\SignatureTimestampTokenExtractor;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use RuntimeException;

final readonly class PadesBtValidator
{
    public function __construct(
        private PadesBbValidator $bbValidator = new PadesBbValidator(),
        private SignatureTimestampTokenExtractor $timestampTokenExtractor = new SignatureTimestampTokenExtractor(),
        private Rfc3161TimestampValidator $timestampValidator = new Rfc3161TimestampValidator(),
        private Rfc3161TimestampValidationPolicy $defaultTimestampPolicy = new Rfc3161TimestampValidationPolicy()
    ) {}

    public function validatePdf(
        string $pdfContent,
        ?Rfc3161TimestampValidationPolicy $timestampPolicy = null
    ): PadesProfileValidationResult {
        $timestampPolicy ??= $this->defaultTimestampPolicy;
        $bb = $this->bbValidator->validatePdf($pdfContent);
        $checks = $bb->checks;
        $messages = $bb->messages;
        $timestampToken = null;

        try {
            $cms = (new PdfSignatureExtractor())
                ->extractBinarySignatureWithoutPadding($pdfContent);
            $timestampToken = $this->timestampTokenExtractor->extract($cms);
        } catch (RuntimeException $exception) {
            $messages[] = $exception->getMessage();
        }

        $checks['signature_timestamp_token'] = $timestampToken !== null;
        $checks['rfc3161_timestamp_token_valid'] = false;

        if ($timestampToken !== null) {
            $timestamp = $this->timestampValidator->validateToken(
                tokenDer: $timestampToken,
                policy: $timestampPolicy
            );
            $checks['rfc3161_timestamp_token_valid'] = $timestamp->valid;

            if (! $timestamp->valid) {
                array_push($messages, ...$timestamp->messages);
            }
        }

        return new PadesProfileValidationResult(
            profile: PadesBaselineProfile::B_T,
            valid: $bb->valid
                && $checks['signature_timestamp_token']
                && $checks['rfc3161_timestamp_token_valid']
                && $messages === [],
            checks: $checks,
            messages: $messages
        );
    }
}
