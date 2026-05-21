<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use RuntimeException;

final readonly class OpenSslSignerProvider implements SignerProviderInterface
{
    public function sign(
        string $data,
        SignatureCredentialInterface $credential,
        SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy()
    ): string {
        if (! $credential instanceof PrivateKeySignatureCredentialInterface) {
            throw new RuntimeException(
                'A credencial informada nao expoe chave privada local para o OpenSSL.'
            );
        }

        if (! $algorithmPolicy->isLocalOpenSslSupported()) {
            throw new RuntimeException(
                'RSA-PSS requer signer provider externo com suporte explicito a padding PSS.'
            );
        }

        $signature = '';

        $success = openssl_sign(
            data: $data,
            signature: $signature,
            private_key: $credential->getPrivateKey(),
            algorithm: $algorithmPolicy->openSslDigestAlgorithm()
        );

        if (! $success) {
            throw new RuntimeException(
                'Nao foi possivel assinar os dados.'
            );
        }

        return $signature;
    }
}
