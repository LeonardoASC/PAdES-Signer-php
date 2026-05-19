<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationReportFactory
{
    /**
     * @return array<string,mixed>
     */
    public function create(
        LtvValidationMaterial $material
    ): array {
        return (new LtvValidationReport())
            ->generate($material);
    }

    public function createJson(
        LtvValidationMaterial $material
    ): string {
        return (
            new LtvValidationReportJsonSerializer()
        )->serialize(
            $this->create($material)
        );
    }
}