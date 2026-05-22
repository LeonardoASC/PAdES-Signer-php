<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

final readonly class CryptographicPreservationStrategy
{
    /**
     * @param array<string> $acceptedHashAlgorithms
     */
    public function __construct(
        public int $renewalIntervalDays = 365,
        public array $acceptedHashAlgorithms = ['sha256', 'sha384', 'sha512'],
        public bool $refreshValidationMaterialBeforeTimestamp = true
    ) {}

    public function shouldRenew(
        \DateTimeInterface $lastArchiveTimestamp,
        ?\DateTimeInterface $now = null
    ): bool {
        $now ??= new \DateTimeImmutable();
        $nextRenewal = \DateTimeImmutable::createFromInterface($lastArchiveTimestamp)
            ->modify('+' . $this->renewalIntervalDays . ' days');

        return $now >= $nextRenewal;
    }
}
