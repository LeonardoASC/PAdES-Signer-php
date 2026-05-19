<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use RuntimeException;

final readonly class X509PublicKeyDerExtractor
{
    public function extractSubjectPublicKeyDer(
        string $certificatePem
    ): string {
        $publicKey = openssl_pkey_get_public(
            $certificatePem
        );

        if ($publicKey === false) {
            throw new RuntimeException(
                'Não foi possível obter chave pública.'
            );
        }

        $details = openssl_pkey_get_details(
            $publicKey
        );

        if (
            $details === false
            || ! isset($details['key'])
        ) {
            throw new RuntimeException(
                'Não foi possível obter detalhes da chave pública.'
            );
        }

        return $this->pemToDer(
            $details['key']
        );
    }

    private function pemToDer(
        string $pem
    ): string {
        $clean = preg_replace(
            '/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s/',
            '',
            $pem
        );

        return base64_decode(
            $clean,
            true
        ) ?: '';
    }
}