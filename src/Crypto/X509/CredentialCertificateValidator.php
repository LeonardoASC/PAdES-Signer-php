<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Validation\TrustValidationResult;
use NihilLabs\Pades\Validation\TrustValidatorInterface;

final readonly class CredentialCertificateValidator implements TrustValidatorInterface
{
    public function __construct(
        private X509CertificateValidator $validator = new X509CertificateValidator(),
        private CertificateValidationContext $context = new CertificateValidationContext(),
        private CertificateValidationPolicy $policy = new CertificateValidationPolicy()
    ) {}

    public function validateCredential(
        SignatureCredentialInterface $credential
    ): TrustValidationResult {
        $result = $this->validator->validate(
            certificatePem: $credential->getCertificatePem(),
            context: $this->context,
            policy: $this->policy
        );

        return new TrustValidationResult(
            trusted: $result->valid,
            messages: $result->messages
        );
    }
}
