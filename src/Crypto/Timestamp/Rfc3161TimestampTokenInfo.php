<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

final readonly class Rfc3161TimestampTokenInfo
{
    /**
     * @param array<string> $certificatesPem
     */
    public function __construct(
        public int $status,
        public string $policyOid,
        public string $hashAlgorithmOid,
        public string $hashedMessage,
        public \DateTimeImmutable $genTime,
        public ?string $nonce = null,
        public array $certificatesPem = []
    ) {}
}
