<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class X509PublicKeyDerExtractor
{
    public function extractSubjectPublicKeyDer(
        string $certificatePem
    ): string {
        return $this->subjectPublicKeyInfoDer($certificatePem);
    }

    public function extractSubjectPublicKeyBitStringValue(
        string $certificatePem
    ): string {
        $subjectPublicKeyInfo = $this->subjectPublicKeyInfoDer($certificatePem);

        $reader = new DerReader();
        $children = $reader->children($subjectPublicKeyInfo);

        if (count($children) < 2 || $children[1]['tag'] !== 0x03) {
            throw new RuntimeException(
                'SubjectPublicKey BIT STRING nao encontrado.'
            );
        }

        $bitString = $children[1]['content'];

        if ($bitString === '') {
            throw new RuntimeException(
                'SubjectPublicKey BIT STRING vazio.'
            );
        }

        return substr($bitString, 1);
    }

    private function subjectPublicKeyInfoDer(
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
