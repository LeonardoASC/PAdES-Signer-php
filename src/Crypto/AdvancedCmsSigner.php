<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\ContentInfoBuilder;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesBuilder;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesSigner;
use NihilLabs\Pades\Crypto\Cades\SignedDataBuilder;
use NihilLabs\Pades\Crypto\Cades\SignerInfoBuilder;

final readonly class AdvancedCmsSigner implements CmsSignerInterface
{
    public function __construct(
        private PfxCertificate $certificate
    ) {}

    public function signDetachedDer(
        string $data
    ): string {
        $signedAttributes = (new SignedAttributesBuilder())
            ->build(
                data: $data,
                certificatePem: $this->certificate->getPublicCertificate()
            );

        $encryptedDigest = (new SignedAttributesSigner(
            $this->certificate
        ))->sign($signedAttributes);

        $signerInfo = (new SignerInfoBuilder())
            ->build(
                certificate: $this->certificate,
                signedAttributes: $signedAttributes,
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