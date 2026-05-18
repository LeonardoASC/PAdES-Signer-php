<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

use NihilLabs\Pades\Certificate\PfxCertificate;
use RuntimeException;

final readonly class DigitalSigner
{
    public function __construct(
        private PfxCertificate $certificate
    ) {}

    public function sign(string $data): string
    {
        $signature = '';

        $success = openssl_sign(
            data: $data,
            signature: $signature,
            private_key: $this->certificate->getPrivateKey(),
            algorithm: OPENSSL_ALGO_SHA256
        );

        if (! $success) {
            throw new RuntimeException(
                'Não foi possível assinar os dados.'
            );
        }

        return $signature;
    }

    public function verify(string $data, string $signature): bool
    {
        $result = openssl_verify(
            data: $data,
            signature: $signature,
            public_key: $this->certificate->getPublicCertificate(),
            algorithm: OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }
}