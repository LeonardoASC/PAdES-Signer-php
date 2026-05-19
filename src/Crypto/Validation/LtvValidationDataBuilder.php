<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationDataBuilder
{
    /**
     * @return array<string>
     */
    public function buildUnsignedAttributes(
        LtvValidationMaterial $material
    ): array {
        return (new ValidationDataEmbedder())
            ->buildUnsignedAttributes(
                ocspResponsesDer: $material->ocspResponsesDer
            );
    }
}