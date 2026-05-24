<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class X509CertificateIdentity
{
    public function __construct(
        public string $pem,
        public string $fingerprint,
        public string $subjectNameDer,
        public string $issuerNameDer,
        public ?string $subjectKeyIdentifier,
        public ?string $authorityKeyIdentifier,
        public bool $selfIssued
    ) {}
}
