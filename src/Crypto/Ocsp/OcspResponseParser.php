<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspResponseParser
{
    public function isParsable(
        string $responseDer
    ): bool {
        if ($responseDer === '') {
            return false;
        }

        return ord($responseDer[0]) === 0x30;
    }
}