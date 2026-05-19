<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationReportExporter
{
    public function export(
        string $path,
        LtvValidationMaterial $material
    ): void {
        $json = (
            new LtvValidationReportFactory()
        )->createJson($material);

        (new LtvValidationReportFileWriter())
            ->write(
                $path,
                $json
            );
    }
}