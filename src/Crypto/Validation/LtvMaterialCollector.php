<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvMaterialCollector
{
    public function collect(
        ValidationMaterial $validationMaterial
    ): LtvValidationMaterial {
        return new LtvValidationMaterial(
            certificatesDer: $validationMaterial->certificatesDer,
            ocspResponsesDer: $validationMaterial->ocspResponsesDer,
            crlsDer: $validationMaterial->crlsDer
        );
    }
}