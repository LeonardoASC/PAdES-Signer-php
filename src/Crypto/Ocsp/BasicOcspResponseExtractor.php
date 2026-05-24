<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class BasicOcspResponseExtractor
{
    public function extract(
        string $responseDer
    ): ?string {
        try {
            return (new OcspResponseParser())->parse($responseDer)->basicResponseDer;
        } catch (RuntimeException) {
        }

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

        $octetPosition = $this->findOctetStringOffset(
            responseDer: $responseDer,
            startOffset: $position + strlen($oid)
        );

        if ($octetPosition === null) {
            return null;
        }

        try {
            $offset = $octetPosition;
            $octetString = (new DerReader())
                ->readTlv($responseDer, $offset);
        } catch (RuntimeException) {
            return null;
        }

        if ($octetString['tag'] !== 0x04) {
            return null;
        }

        return $octetString['content'];
    }

    private function findOctetStringOffset(
        string $responseDer,
        int $startOffset
    ): ?int {
        $length = strlen($responseDer);

        for ($offset = $startOffset; $offset < $length; $offset++) {
            if (ord($responseDer[$offset]) === 0x04) {
                return $offset;
            }
        }

        return null;
    }
}
