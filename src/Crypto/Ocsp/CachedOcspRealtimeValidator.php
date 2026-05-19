<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class CachedOcspRealtimeValidator
{
    public function __construct(
        private OcspRealtimeValidator $validator,
        private OcspResponseCache $cache
    ) {}

    public function validate(
        string $cacheKey,
        string $certificatePem,
        string $issuerNameDer,
        string $issuerPublicKeyDer,
        string $serialNumberHex
    ): OcspValidationResult {
        $cached = $this->cache->get(
            $cacheKey
        );

        if ($cached !== null) {
            return (new OcspValidator())
                ->validate($cached);
        }

        $result = $this->validator->validate(
            certificatePem: $certificatePem,
            issuerNameDer: $issuerNameDer,
            issuerPublicKeyDer: $issuerPublicKeyDer,
            serialNumberHex: $serialNumberHex
        );

        return $result;
    }
}