<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;

final readonly class SignedDataBuilder
{
    public function build(
        PfxCertificate|SignatureCredentialInterface $certificate,
        string $signerInfo
    ): string {
        $credential = $this->normalizeCredential($certificate);

        return Der::sequence(
            Der::integer(1)
                . Der::set($this->digestAlgorithm())
                . $this->encapContentInfo()
                . $this->certificates($credential)
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
        SignatureCredentialInterface $credential
    ): string {
        $certificates = [];

        foreach ($credential->getCertificateChainPem() as $pem) {
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

    private function normalizeCredential(
        PfxCertificate|SignatureCredentialInterface $credential
    ): SignatureCredentialInterface {
        return $credential instanceof PfxCertificate
            ? new PfxSignatureCredential($credential)
            : $credential;
    }
}
