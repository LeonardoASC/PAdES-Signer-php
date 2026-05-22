<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class CertificateValidationContext
{
    public function __construct(
        public ?\DateTimeInterface $validationTime = null,
        public ?\DateTimeInterface $signingTime = null,
        public ?\DateTimeInterface $timestampTime = null
    ) {}
}
