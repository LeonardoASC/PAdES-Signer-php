<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Certificate;

final readonly class PfxCertificateMetadata
{
    /**
     * @param array<string, mixed> $subject
     * @param array<string, mixed> $issuer
     * @param array<string> $certificateChainPem
     */
    public function __construct(
        public ?string $commonName,
        public string $serialNumberHex,
        public array $subject,
        public array $issuer,
        public ?\DateTimeImmutable $validFrom,
        public ?\DateTimeImmutable $validTo,
        public string $certificatePem,
        public array $certificateChainPem
    ) {}

    public static function fromCertificate(PfxCertificate $certificate): self
    {
        $info = $certificate->getInfo();

        return new self(
            commonName: $info['subject']['CN'] ?? null,
            serialNumberHex: $certificate->getSerialNumberHex(),
            subject: $info['subject'] ?? [],
            issuer: $info['issuer'] ?? [],
            validFrom: self::dateFromTimestamp($info['validFrom_time_t'] ?? null),
            validTo: self::dateFromTimestamp($info['validTo_time_t'] ?? null),
            certificatePem: $certificate->getPublicCertificate(),
            certificateChainPem: $certificate->getCertificateChain()
        );
    }

    private static function dateFromTimestamp(mixed $timestamp): ?\DateTimeImmutable
    {
        if (! is_int($timestamp)) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($timestamp);
    }
}
