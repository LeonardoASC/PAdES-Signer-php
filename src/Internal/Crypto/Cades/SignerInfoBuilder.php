<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;

final readonly class SignerInfoBuilder
{
    public function build(
        PfxCertificate|SignatureCredentialInterface $certificate,
        string $signedAttributesForCms,
        string $encryptedDigest,
        ?string $unsignedAttributesForCms = null
    ): string {
        $credential = $this->normalizeCredential($certificate);

        $content = Der::integer(1)
            . $this->sid($credential)
            . $this->digestAlgorithm()
            . $signedAttributesForCms
            . $this->signatureAlgorithm()
            . Der::octetString($encryptedDigest);

        if ($unsignedAttributesForCms !== null) {
            $content .= $unsignedAttributesForCms;
        }

        return Der::sequence($content);
    }

    private function sid(SignatureCredentialInterface $credential): string
    {
        return (new IssuerAndSerialNumber())
            ->build($credential->getCertificatePem());
    }

    private function normalizeCredential(
        PfxCertificate|SignatureCredentialInterface $credential
    ): SignatureCredentialInterface {
        return $credential instanceof PfxCertificate
            ? new PfxSignatureCredential($credential)
            : $credential;
    }

    private function digestAlgorithm(): string
    {
        return Der::sha256AlgorithmIdentifier();
    }

    private function signatureAlgorithm(): string
    {
        return Der::sequence(
            Der::oid('2a864886f70d01010b')
                . Der::null()
        );
    }
}
