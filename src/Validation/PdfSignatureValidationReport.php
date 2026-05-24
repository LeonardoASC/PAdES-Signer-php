<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Pdf\EmbeddedPdfSignature;

final readonly class PdfSignatureValidationReport
{
    /**
     * @param array<string, bool> $checks
     * @param list<string> $messages
     */
    public function __construct(
        public EmbeddedPdfSignature $signature,
        public bool $valid,
        public array $checks,
        public array $messages,
        public ?string $digestAlgorithm,
        public ?string $calculatedDigestHex,
        public ?string $cmsMessageDigestHex,
        public ?string $signerCertificatePem
    ) {}
}
