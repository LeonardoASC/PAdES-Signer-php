<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationReportJsonSerializer
{
    /**
     * @param array<string,mixed> $report
     */
    public function serialize(
        array $report
    ): string {
        return json_encode(
            $report,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
    }
}