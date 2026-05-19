<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class CertStatusExtractor
{
    public function extract(
        string $basicOcspResponse
    ): ?string {
        if (str_contains($basicOcspResponse, "\xA0")) {
            return 'good';
        }

        if (str_contains($basicOcspResponse, "\xA1")) {
            return 'revoked';
        }

        if (str_contains($basicOcspResponse, "\xA2")) {
            return 'unknown';
        }

        return null;
    }

    public function isGood(
        string $basicOcspResponse
    ): bool {
        return $this->extract(
            $basicOcspResponse
        ) === 'good';
    }
}