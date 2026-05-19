<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationInspector
{
    /**
     * @return array<string,mixed>
     */
    public function inspect(
        LtvValidationMaterial $material
    ): array {
        return [
            'certificates' => count(
                $material->certificatesDer
            ),

            'ocsp_responses' => count(
                $material->ocspResponsesDer
            ),

            'crls' => count(
                $material->crlsDer
            ),

            'ltv_capable' => (
                new LtvValidationProfile()
            )->isLtvCapable($material),
        ];
    }
}