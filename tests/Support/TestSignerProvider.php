<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests\Support;

use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Signing\SignerProviderInterface;

final readonly class TestSignerProvider implements SignerProviderInterface
{
    public function sign(
        string $data,
        SignatureCredentialInterface $credential,
        SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy()
    ): string {
        return str_repeat("\x01", 256);
    }
}
