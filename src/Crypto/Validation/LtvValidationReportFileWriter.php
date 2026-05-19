<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class LtvValidationReportFileWriter
{
    public function write(
        string $path,
        string $json
    ): void {
        file_put_contents(
            $path,
            $json
        );
    }
}