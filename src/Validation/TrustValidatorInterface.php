<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Signing\SignatureCredentialInterface;

interface TrustValidatorInterface
{
    public function validateCredential(
        SignatureCredentialInterface $credential
    ): TrustValidationResult;
}
