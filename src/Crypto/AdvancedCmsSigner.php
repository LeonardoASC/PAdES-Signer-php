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
use NihilLabs\Pades\Crypto\Cades\SignatureTimestampTokenAttribute;
use NihilLabs\Pades\Crypto\Cades\UnsignedAttributesBuilder;
use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampRequest;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Crypto\Timestamp\TimestampResponseParser;

final readonly class AdvancedCmsSigner implements CmsSignerInterface
{
    public function __construct(
        private PfxCertificate $certificate,
        private ?TimestampClientInterface $timestampClient = null
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

        $unsignedAttributes = null;

        if ($this->timestampClient !== null) {
            $timestampRequest = (new Rfc3161TimestampRequest())
                ->build($encryptedDigest);

            $timestampResponse = $this->timestampClient
                ->requestToken($timestampRequest);

            $timestampToken = (new TimestampResponseParser())
                ->extractToken($timestampResponse);

            $timestampAttribute = (new SignatureTimestampTokenAttribute())
                ->build($timestampToken);

            $unsignedAttributes = (new UnsignedAttributesBuilder())
                ->build([$timestampAttribute]);
        }

        $signerInfo = (new SignerInfoBuilder())
            ->build(
                certificate: $this->certificate,
                signedAttributesForCms: $signedAttributesForCms,
                encryptedDigest: $encryptedDigest,
                unsignedAttributesForCms: $unsignedAttributes
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
