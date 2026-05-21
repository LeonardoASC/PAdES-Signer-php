<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;
use RuntimeException;

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
                . $this->issuerSerial($certificatePem)
        );
    }

    private function issuerSerial(string $certificatePem): string
    {
        $extractor = new X509NameDerExtractor();

        return Der::sequence(
            Der::sequence(
                Der::contextSpecificConstructed(
                    4,
                    $extractor->extractIssuerNameDer($certificatePem)
                )
            )
                . $extractor->extractSerialNumberDer($certificatePem)
        );
    }

    private function pemToDer(string $pem): string
    {
        $clean = preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
            '',
            $pem
        );

        $der = base64_decode($clean, strict: true);

        if ($der === false) {
            throw new RuntimeException(
                'Nao foi possivel converter certificado PEM para DER.'
            );
        }

        return $der;
    }
}
