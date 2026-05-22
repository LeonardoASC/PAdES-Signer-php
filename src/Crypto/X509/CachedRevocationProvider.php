<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class CachedRevocationProvider implements RevocationProviderInterface
{
    public function __construct(
        private RevocationProviderInterface $provider,
        private RevocationCacheInterface $cache
    ) {}

    public function collect(array $certificateChainPem): RevocationMaterial
    {
        $chainKey = $this->chainKey($certificateChainPem);
        $cachedOcsp = $this->cache->get('ocsp', $chainKey);
        $cachedCrl = $this->cache->get('crl', $chainKey);

        if ($cachedOcsp !== null || $cachedCrl !== null) {
            return new RevocationMaterial(
                ocspResponsesDer: $cachedOcsp === null ? [] : [$cachedOcsp],
                crlsDer: $cachedCrl === null ? [] : [$cachedCrl]
            );
        }

        $material = $this->provider->collect($certificateChainPem);

        if ($material->ocspResponsesDer !== []) {
            $this->cache->put('ocsp', $chainKey, $material->ocspResponsesDer[0]);
        }

        if ($material->crlsDer !== []) {
            $this->cache->put('crl', $chainKey, $material->crlsDer[0]);
        }

        return $material;
    }

    /**
     * @param array<string> $certificateChainPem
     */
    private function chainKey(array $certificateChainPem): string
    {
        $normalizer = new CertificateFormatNormalizer();
        $fingerprints = [];

        foreach ($certificateChainPem as $certificatePem) {
            $fingerprints[] = hash('sha256', $normalizer->normalizeToDer($certificatePem));
        }

        return hash('sha256', implode(':', $fingerprints));
    }
}
