<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspResponseStatusExtractor
{
    public function extract(
        string $responseDer
    ): ?int {
        if (
            strlen($responseDer) < 5
        ) {
            return null;
        }

        $integerTagOffset = 2;

        if (
            ord($responseDer[$integerTagOffset]) !== 0x0A
        ) {
            return null;
        }

        return ord($responseDer[$integerTagOffset + 2]);
    }

    public function isSuccessful(
        string $responseDer
    ): bool {
        return $this->extract(
            $responseDer
        ) === 0;
    }
}