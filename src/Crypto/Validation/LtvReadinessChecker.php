<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvReadinessChecker
{
    public function isReady(
        LtvValidationMaterial $material
    ): bool {
        return (
            new LtvCapabilityDetector()
        )->detect($material)['ltv_capable'];
    }
}