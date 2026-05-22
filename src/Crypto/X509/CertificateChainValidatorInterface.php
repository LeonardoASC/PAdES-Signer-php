<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use NihilLabs\Pades\Signing\SignatureCredentialInterface;

interface CertificateChainValidatorInterface
{
    public function validateCredential(
        SignatureCredentialInterface $credential,
        TrustStoreInterface $trustStore
    ): CertificateChainValidationResult;
}
