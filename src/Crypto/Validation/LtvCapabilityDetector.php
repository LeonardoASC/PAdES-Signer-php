<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvCapabilityDetector
{
    /**
     * @return array<string,bool>
     */
    public function detect(
        LtvValidationMaterial $material
    ): array {
        return [
            'has_certificates' => (
                $material->certificatesDer !== []
            ),

            'has_ocsp' => (
                $material->ocspResponsesDer !== []
            ),

            'has_crls' => (
                $material->crlsDer !== []
            ),

            'ltv_capable' => (
                new LtvValidationProfile()
            )->isLtvCapable($material),
        ];
    }
}