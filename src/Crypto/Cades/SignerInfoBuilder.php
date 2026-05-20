<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignerInfoBuilder
{
    public function build(
        PfxCertificate $certificate,
        string $signedAttributesForCms,
        string $encryptedDigest,
        ?string $unsignedAttributesForCms = null
    ): string {
        $content = Der::integer(1)
            . $this->sid($certificate)
            . $this->digestAlgorithm()
            . $signedAttributesForCms
            . $this->signatureAlgorithm()
            . Der::octetString($encryptedDigest);

        if ($unsignedAttributesForCms !== null) {
            $content .= $unsignedAttributesForCms;
        }

        return Der::sequence($content);
    }

    private function sid(PfxCertificate $certificate): string
    {
        return (new IssuerAndSerialNumber())
            ->build($certificate);
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
