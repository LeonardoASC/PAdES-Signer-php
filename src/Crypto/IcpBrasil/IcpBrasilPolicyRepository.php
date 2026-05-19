<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\IcpBrasil;

use RuntimeException;

final class IcpBrasilPolicyRepository
{
    private readonly LpaDownloader $lpaDownloader;
    private readonly LpaParser $lpaParser;
    private readonly PolicyArtifactDownloader $artifactDownloader;
    private readonly PolicyHashCalculator $hashCalculator;
    private readonly IcpBrasilPolicyCache $cache;

    public function __construct(
        ?LpaDownloader $lpaDownloader = null,
        ?LpaParser $lpaParser = null,
        ?PolicyArtifactDownloader $artifactDownloader = null,
        ?PolicyHashCalculator $hashCalculator = null,
        ?IcpBrasilPolicyCache $cache = null,
        private readonly string $lpaUri = LpaDownloader::PADES_LPA_DER_URL
    ) {
        $this->lpaDownloader = $lpaDownloader ?? new LpaDownloader();
        $this->lpaParser = $lpaParser ?? new LpaParser();
        $this->artifactDownloader = $artifactDownloader ?? new PolicyArtifactDownloader();
        $this->hashCalculator = $hashCalculator ?? new PolicyHashCalculator();
        $this->cache = $cache ?? new IcpBrasilPolicyCache();
    }

    public function resolveAdRbPdfPolicy(): ResolvedIcpPolicy
    {
        return $this->resolvePdfPolicy('AD-RB');
    }

    public function resolveAdRtPdfPolicy(): ResolvedIcpPolicy
    {
        return $this->resolvePdfPolicy('AD-RT');
    }

    public function resolveAdRcPdfPolicy(): ResolvedIcpPolicy
    {
        return $this->resolvePdfPolicy('AD-RC');
    }

    public function resolveAdRaPdfPolicy(): ResolvedIcpPolicy
    {
        return $this->resolvePdfPolicy('AD-RA');
    }

    public function resolvePdfPolicy(string $signatureType): ResolvedIcpPolicy
    {
        $signatureType = strtoupper($signatureType);
        $cacheKey = 'resolved:' . $this->lpaUri . ':' . $signatureType;
        $cached = $this->cache->getJson($cacheKey);

        if ($cached !== null) {
            return ResolvedIcpPolicy::fromArray($cached);
        }

        $lpaBytes = $this->cache->rememberBytes(
            'lpa:' . $this->lpaUri,
            fn (): string => $this->lpaDownloader->download($this->lpaUri)
        );

        $policies = $this->lpaParser->parse($lpaBytes);
        $entry = $this->lpaParser->selectLatestCurrentPdfPolicy(
            policies: $policies,
            signatureType: $signatureType
        );

        if ($entry === null) {
            throw new RuntimeException("No current ICP-Brasil {$signatureType} PAdES policy found.");
        }

        $policyBytes = $this->cache->rememberBytes(
            'pa:' . $entry->policyUri,
            fn (): string => $this->artifactDownloader->downloadArtifact($entry->policyUri)
        );

        $policyHash = $this->cachePolicyHash(
            policyUri: $entry->policyUri,
            policyBytes: $policyBytes
        );

        if (! hash_equals($entry->policyHash, $policyHash)) {
            throw new RuntimeException(
                'Downloaded ICP-Brasil policy artifact does not match LPA SHA-256.'
            );
        }

        $resolved = new ResolvedIcpPolicy(
            policyOid: $entry->policyOid,
            policyHash: $policyHash,
            policyUri: $entry->policyUri,
            signatureType: $entry->signatureType,
            format: $entry->format,
            version: $entry->version,
            validFrom: $entry->validFrom,
            validUntil: $entry->validUntil,
            revokedAt: $entry->revokedAt,
            lpaUri: $this->lpaUri,
            artifacts: [
                ...$entry->artifacts,
                'policyUri' => $entry->policyUri,
                'policyHashFromLpaHex' => $entry->policyHashHex(),
                'policyHashCalculatedHex' => strtolower(bin2hex($policyHash)),
                'policyHashMatchesLpa' => true,
            ]
        );

        $this->cache->putJson($cacheKey, $resolved->toArray());

        return $resolved;
    }

    private function cachePolicyHash(
        string $policyUri,
        string $policyBytes
    ): string {
        $cacheKey = 'hash:sha256:' . $policyUri . ':' . hash('sha256', $policyBytes);
        $cached = $this->cache->getBytes($cacheKey);

        if ($cached !== null && strlen($cached) === 32) {
            return $cached;
        }

        $hash = $this->hashCalculator->sha256($policyBytes);
        $this->cache->putBytes($cacheKey, $hash);

        return $hash;
    }
}
