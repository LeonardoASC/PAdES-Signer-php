<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class BasicOcspResponseExtractor
{
    public function extract(
        string $responseDer
    ): ?string {
        $oid = hex2bin(
            '2B0601050507300101'
        );

        $position = strpos(
            $responseDer,
            $oid
        );

        if ($position === false) {
            return null;
        }

        $octetStringTag = "\x04";

        $octetPosition = strpos(
            $responseDer,
            $octetStringTag,
            $position
        );

        if ($octetPosition === false) {
            return null;
        }

        $length = ord(
            $responseDer[$octetPosition + 1]
        );

        return substr(
            $responseDer,
            $octetPosition + 2,
            $length
        );
    }
}