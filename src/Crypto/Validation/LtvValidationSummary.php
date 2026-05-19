<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationSummary
{
    public function summarize(
        LtvValidationMaterial $material
    ): string {
        $inspection = (
            new LtvValidationInspector()
        )->inspect($material);

        return sprintf(
            'LTV capable: %s | Certificates: %d | OCSP: %d | CRLs: %d',
            $inspection['ltv_capable']
                ? 'yes'
                : 'no',

            $inspection['certificates'],
            $inspection['ocsp_responses'],
            $inspection['crls']
        );
    }
}