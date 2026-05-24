<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Crl;

final readonly class ParsedCrl
{
    /**
     * @param list<string> $revokedSerialNumbersHex
     */
    public function __construct(
        public string $tbsCertListDer,
        public string $issuerNameDer,
        public \DateTimeImmutable $thisUpdate,
        public ?\DateTimeImmutable $nextUpdate,
        public string $signatureAlgorithmOid,
        public string $signature,
        public array $revokedSerialNumbersHex,
        public ?string $authorityKeyIdentifier = null
    ) {}

    public function isRevoked(string $serialNumberHex): bool
    {
        return in_array(strtoupper(ltrim($serialNumberHex, '0')), $this->revokedSerialNumbersHex, true);
    }
}
