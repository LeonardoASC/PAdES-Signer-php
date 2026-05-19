<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\IcpBrasil;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class LpaPolicyEntry
{
    /**
     * @param array<string, mixed> $artifacts
     */
    public function __construct(
        public string $policyOid,
        public string $policyUri,
        public string $policyHash,
        public string $hashAlgorithmOid,
        public string $signatureType,
        public string $format,
        public ?string $version,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validUntil,
        public ?DateTimeImmutable $revokedAt = null,
        public array $artifacts = []
    ) {
        if ($this->policyOid === '') {
            throw new InvalidArgumentException('Policy OID must not be empty.');
        }

        if ($this->policyUri === '') {
            throw new InvalidArgumentException('Policy URI must not be empty.');
        }

        if ($this->policyHash === '') {
            throw new InvalidArgumentException('Policy hash must not be empty.');
        }
    }

    public function policyHashHex(): string
    {
        return strtolower(bin2hex($this->policyHash));
    }

    public function isPdfPolicy(): bool
    {
        return strtoupper($this->format) === 'PADES'
            || stripos($this->policyUri, 'PA_PAdES_') !== false;
    }

    public function isSignatureType(string $signatureType): bool
    {
        return strtoupper($this->signatureType) === strtoupper($signatureType);
    }

    public function isCurrent(?DateTimeImmutable $at = null): bool
    {
        $at ??= new DateTimeImmutable('now');

        if ($this->validFrom !== null && $this->validFrom > $at) {
            return false;
        }

        if ($this->validUntil !== null && $this->validUntil < $at) {
            return false;
        }

        return ! ($this->revokedAt !== null && $this->revokedAt <= $at);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'policyOid' => $this->policyOid,
            'policyUri' => $this->policyUri,
            'policyHashHex' => $this->policyHashHex(),
            'hashAlgorithmOid' => $this->hashAlgorithmOid,
            'signatureType' => $this->signatureType,
            'format' => $this->format,
            'version' => $this->version,
            'validFrom' => $this->validFrom?->format(DATE_ATOM),
            'validUntil' => $this->validUntil?->format(DATE_ATOM),
            'revokedAt' => $this->revokedAt?->format(DATE_ATOM),
            'artifacts' => $this->artifacts,
        ];
    }
}
