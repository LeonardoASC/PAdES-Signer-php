<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

use RuntimeException;

final readonly class OpenSslSignerProvider implements SignerProviderInterface
{
    public function sign(
        string $data,
        SignatureCredentialInterface $credential
    ): string {
        if (! $credential instanceof PrivateKeySignatureCredentialInterface) {
            throw new RuntimeException(
                'A credencial informada nao expoe chave privada local para o OpenSSL.'
            );
        }

        $signature = '';

        $success = openssl_sign(
            data: $data,
            signature: $signature,
            private_key: $credential->getPrivateKey(),
            algorithm: OPENSSL_ALGO_SHA256
        );

        if (! $success) {
            throw new RuntimeException(
                'Nao foi possivel assinar os dados.'
            );
        }

        return $signature;
    }
}
