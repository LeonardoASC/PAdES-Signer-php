<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationReport
{
    /**
     * @return array<string,mixed>
     */
    public function generate(
        LtvValidationMaterial $material
    ): array {
        return [
            'summary' => (
                new LtvValidationSummary()
            )->summarize($material),

            'capabilities' => (
                new LtvCapabilityDetector()
            )->detect($material),

            'ready' => (
                new LtvReadinessChecker()
            )->isReady($material),
        ];
    }
}