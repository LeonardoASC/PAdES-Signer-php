<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\IcpBrasil\IcpBrasilPolicyRepository;
use NihilLabs\Pades\Crypto\IcpBrasil\ResolvedIcpPolicy;

final readonly class IcpBrasilSignaturePolicy
{
    /**
     * @param array<string, mixed> $artifacts
     */
    public function __construct(
        public string $policyOid,
        public string $policyHash,
        public ?string $policyUri = null,
        public ?string $version = null,
        public array $artifacts = []
    ) {
        if ($this->policyHash === '') {
            throw new InvalidArgumentException('Policy hash must not be empty.');
        }
    }

    public static function adRtPdfPlaceholder(
        string $policyHash,
        ?string $policyUri = null
    ): self {
        return new self(
            policyOid: '2.16.76.1.7.1.12.1.2',
            policyHash: $policyHash,
            policyUri: $policyUri
        );
    }

    public static function fromRepository(
        ?IcpBrasilPolicyRepository $repository = null,
        string $signatureType = 'AD-RT'
    ): self {
        $repository ??= new IcpBrasilPolicyRepository();

        return self::fromResolvedPolicy(
            match (strtoupper($signatureType)) {
                'AD-RB' => $repository->resolveAdRbPdfPolicy(),
                'AD-RT' => $repository->resolveAdRtPdfPolicy(),
                'AD-RC' => $repository->resolveAdRcPdfPolicy(),
                'AD-RA' => $repository->resolveAdRaPdfPolicy(),
                default => $repository->resolvePdfPolicy($signatureType),
            }
        );
    }

    public static function fromResolvedPolicy(
        ResolvedIcpPolicy $policy
    ): self {
        return new self(
            policyOid: $policy->policyOid,
            policyHash: $policy->policyHash,
            policyUri: $policy->policyUri,
            version: $policy->version,
            artifacts: $policy->artifacts
        );
    }
}
