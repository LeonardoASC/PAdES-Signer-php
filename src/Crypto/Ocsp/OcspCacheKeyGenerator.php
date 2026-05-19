<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspCacheKeyGenerator
{
    public function generate(
        string $issuerNameDer,
        string $serialNumberHex
    ): string {
        return sha1(
            $issuerNameDer
            . ':'
            . strtoupper($serialNumberHex)
        );
    }
}