<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use RuntimeException;

final readonly class SignedAttributesSigner
{
    public function __construct(
        private PfxCertificate $certificate,
        private SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy()
    ) {}

    public function sign(string $signedAttributes): string
    {
        $signature = '';

        $success = openssl_sign(
            data: $signedAttributes,
            signature: $signature,
            private_key: $this->certificate->getPrivateKey(),
            algorithm: $this->algorithmPolicy->openSslDigestAlgorithm()
        );

        if (! $success) {
            throw new RuntimeException(
                'Não foi possível assinar os signed attributes.'
            );
        }

        return $signature;
    }

    public function verify(
        string $signedAttributes,
        string $signature
    ): bool {
        $result = openssl_verify(
            data: $signedAttributes,
            signature: $signature,
            public_key: $this->certificate->getPublicCertificate(),
            algorithm: $this->algorithmPolicy->openSslDigestAlgorithm()
        );

        return $result === 1;
    }
}
