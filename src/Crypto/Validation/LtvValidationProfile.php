<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationProfile
{
    public function isLtvCapable(
        LtvValidationMaterial $material
    ): bool {
        return $material->hasCertificates()
            && $material->hasRevocationData();
    }
}