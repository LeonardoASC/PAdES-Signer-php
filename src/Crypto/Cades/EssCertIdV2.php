<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class EssCertIdV2
{
    public function build(string $certificatePem): string
    {
        $certificateDer = $this->pemToDer($certificatePem);

        $hash = hash(
            'sha256',
            $certificateDer,
            binary: true
        );

        return Der::sequence(
            Der::octetString($hash)
        );
    }

    private function pemToDer(string $pem): string
    {
        $clean = preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
            '',
            $pem
        );

        return base64_decode($clean, strict: true);
    }
}