<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class CertificateValidationPolicy
{
    /**
     * @param array<string> $allowedKeyUsages
     * @param array<string> $allowedExtendedKeyUsages
     */
    public function __construct(
        public array $allowedKeyUsages = ['digitalSignature', 'nonRepudiation', 'contentCommitment'],
        public array $allowedExtendedKeyUsages = ['emailProtection', 'clientAuth', 'codeSigning', '1.3.6.1.5.5.7.3.36'],
        public bool $requireKeyUsage = false,
        public bool $requireExtendedKeyUsage = false
    ) {}
}
