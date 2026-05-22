<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Validation\TrustValidationResult;
use NihilLabs\Pades\Validation\TrustValidatorInterface;

final readonly class TrustStoreCredentialValidator implements TrustValidatorInterface
{
    public function __construct(
        private TrustStoreInterface $trustStore,
        private CertificateChainValidatorInterface $chainValidator = new OpenSslCertificateChainValidator()
    ) {}

    public function validateCredential(
        SignatureCredentialInterface $credential
    ): TrustValidationResult {
        $result = $this->chainValidator->validateCredential(
            credential: $credential,
            trustStore: $this->trustStore
        );

        return new TrustValidationResult(
            trusted: $result->trusted,
            messages: $result->messages
        );
    }
}
