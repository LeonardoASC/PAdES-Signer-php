<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationReportFileWriter
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