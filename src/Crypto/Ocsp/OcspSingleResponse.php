<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspSingleResponse
{
    public function __construct(
        public string $hashAlgorithmOid,
        public string $issuerNameHash,
        public string $issuerKeyHash,
        public string $serialNumberHex,
        public string $certificateStatus,
        public \DateTimeImmutable $thisUpdate,
        public ?\DateTimeImmutable $nextUpdate = null
    ) {}
}
