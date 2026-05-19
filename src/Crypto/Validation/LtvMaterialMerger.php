<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvMaterialMerger
{
    public function merge(
        LtvValidationMaterial $a,
        LtvValidationMaterial $b
    ): LtvValidationMaterial {
        return new LtvValidationMaterial(
            certificatesDer: array_values(
                array_unique([
                    ...$a->certificatesDer,
                    ...$b->certificatesDer,
                ])
            ),

            ocspResponsesDer: array_values(
                array_unique([
                    ...$a->ocspResponsesDer,
                    ...$b->ocspResponsesDer,
                ])
            ),

            crlsDer: array_values(
                array_unique([
                    ...$a->crlsDer,
                    ...$b->crlsDer,
                ])
            )
        );
    }
}