<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;

interface SignerProviderInterface
{
    public function sign(
        string $data,
        SignatureCredentialInterface $credential,
        SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy()
    ): string;
}
