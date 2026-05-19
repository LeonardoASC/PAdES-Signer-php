<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspCacheAwareValidator
{
    public function __construct(
        private CachedOcspRealtimeValidator $validator,
        private OcspCacheKeyGenerator $keyGenerator
    ) {}

    public function validate(
        string $certificatePem,
        string $issuerNameDer,
        string $issuerPublicKeyDer,
        string $serialNumberHex
    ): OcspValidationResult {
        $cacheKey = $this->keyGenerator
            ->generate(
                issuerNameDer: $issuerNameDer,
                serialNumberHex: $serialNumberHex
            );

        return $this->validator->validate(
            cacheKey: $cacheKey,
            certificatePem: $certificatePem,
            issuerNameDer: $issuerNameDer,
            issuerPublicKeyDer: $issuerPublicKeyDer,
            serialNumberHex: $serialNumberHex
        );
    }
}