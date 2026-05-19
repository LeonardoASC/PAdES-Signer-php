<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\ContentInfoBuilder;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesBuilder;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesSigner;
use NihilLabs\Pades\Crypto\Cades\SignedDataBuilder;
use NihilLabs\Pades\Crypto\Cades\SignerInfoBuilder;
use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class AdvancedCmsSigner implements CmsSignerInterface
{
    public function __construct(
        private PfxCertificate $certificate
    ) {}

    public function signDetachedDer(
        string $data
    ): string {
        $signedAttributesForSignature = (new SignedAttributesBuilder())
            ->build(
                data: $data,
                certificatePem: $this->certificate->getPublicCertificate()
            );

        $signedAttributesForCms = Der::contextSpecificImplicitFromEncoded(
            0,
            $signedAttributesForSignature
        );

        $encryptedDigest = (new SignedAttributesSigner(
            $this->certificate
        ))->sign($signedAttributesForSignature);

        $signerInfo = (new SignerInfoBuilder())
            ->build(
                certificate: $this->certificate,
                signedAttributesForCms: $signedAttributesForCms,
                encryptedDigest: $encryptedDigest
            );

        $signedData = (new SignedDataBuilder())
            ->build(
                certificate: $this->certificate,
                signerInfo: $signerInfo
            );

        return (new ContentInfoBuilder())
            ->build($signedData);
    }

    public function getCertificate(): PfxCertificate
    {
        return $this->certificate;
    }
}
