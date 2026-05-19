<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\IcpBrasil;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ResolvedIcpPolicy
{
    /**
     * @param array<string, mixed> $artifacts
     */
    public function __construct(
        public string $policyOid,
        public string $policyHash,
        public string $policyUri,
        public string $signatureType,
        public string $format,
        public ?string $version = null,
        public ?DateTimeImmutable $validFrom = null,
        public ?DateTimeImmutable $validUntil = null,
        public ?DateTimeImmutable $revokedAt = null,
        public ?string $lpaUri = null,
        public array $artifacts = []
    ) {
        if ($this->policyOid === '') {
            throw new InvalidArgumentException('Policy OID must not be empty.');
        }

        if ($this->policyHash === '') {
            throw new InvalidArgumentException('Policy hash must not be empty.');
        }

        if (strlen($this->policyHash) !== 32) {
            throw new InvalidArgumentException('Resolved policy hash must be a SHA-256 digest.');
        }

        if ($this->policyUri === '') {
            throw new InvalidArgumentException('Policy URI must not be empty.');
        }
    }

    public function policyHashHex(): string
    {
        return strtolower(bin2hex($this->policyHash));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'policyOid' => $this->policyOid,
            'policyHashHex' => $this->policyHashHex(),
            'policyUri' => $this->policyUri,
            'signatureType' => $this->signatureType,
            'format' => $this->format,
            'version' => $this->version,
            'validFrom' => $this->validFrom?->format(DATE_ATOM),
            'validUntil' => $this->validUntil?->format(DATE_ATOM),
            'revokedAt' => $this->revokedAt?->format(DATE_ATOM),
            'lpaUri' => $this->lpaUri,
            'artifacts' => $this->artifacts,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $hashHex = $data['policyHashHex'] ?? null;

        if (! is_string($hashHex) || preg_match('/^[0-9a-fA-F]{64}$/', $hashHex) !== 1) {
            throw new InvalidArgumentException('Resolved policy cache has an invalid hash.');
        }

        return new self(
            policyOid: self::stringValue($data, 'policyOid'),
            policyHash: hex2bin($hashHex) ?: '',
            policyUri: self::stringValue($data, 'policyUri'),
            signatureType: self::stringValue($data, 'signatureType'),
            format: self::stringValue($data, 'format'),
            version: isset($data['version']) && is_string($data['version'])
                ? $data['version']
                : null,
            validFrom: self::dateValue($data, 'validFrom'),
            validUntil: self::dateValue($data, 'validUntil'),
            revokedAt: self::dateValue($data, 'revokedAt'),
            lpaUri: isset($data['lpaUri']) && is_string($data['lpaUri'])
                ? $data['lpaUri']
                : null,
            artifacts: isset($data['artifacts']) && is_array($data['artifacts'])
                ? $data['artifacts']
                : []
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function stringValue(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("Resolved policy cache missing {$key}.");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function dateValue(array $data, string $key): ?DateTimeImmutable
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        return new DateTimeImmutable($value);
    }
}
