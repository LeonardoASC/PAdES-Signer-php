<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignedDataBuilder
{
    public function build(
        PfxCertificate $certificate,
        string $signerInfo
    ): string {
        return Der::sequence(
            Der::integer(1)
                . Der::set($this->digestAlgorithm())
                . $this->encapContentInfo()
                . $this->certificates($certificate)
                . Der::set($signerInfo)
        );
    }

    private function digestAlgorithm(): string
    {
        return Der::sha256AlgorithmIdentifier();
    }

    private function encapContentInfo(): string
    {
        return Der::sequence(
            Der::oid('2a864886f70d010701')
        );
    }

    private function certificates(
        PfxCertificate $certificate
    ): string {
        $certificates = [];

        foreach ($certificate->getCertificateChain() as $pem) {
            $certificates[] = $this->pemToDer($pem);
        }

        return Der::contextSpecificConstructed(
            0,
            Der::sortedSetContent($certificates)
        );
    }

    private function pemToDer(string $pem): string
    {
        $clean = preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
            '',
            $pem
        );

        $decoded = base64_decode($clean, true);

        if ($decoded === false) {
            throw new \RuntimeException(
                'Nao foi possivel converter certificado PEM para DER.'
            );
        }

        return $decoded;
    }
}
