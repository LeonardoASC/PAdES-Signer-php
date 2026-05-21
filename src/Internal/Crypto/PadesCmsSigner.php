<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Internal\Crypto\Cades\ContentInfoBuilder;
use NihilLabs\Pades\Internal\Crypto\Cades\SignedAttributesBuilder;
use NihilLabs\Pades\Internal\Crypto\Cades\SignedDataBuilder;
use NihilLabs\Pades\Internal\Crypto\Cades\SignerInfoBuilder;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Internal\Crypto\Cades\SignatureTimestampTokenAttribute;
use NihilLabs\Pades\Internal\Crypto\Cades\UnsignedAttributesBuilder;
use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampRequest;
use NihilLabs\Pades\Crypto\Timestamp\TimestampResponseParser;
use NihilLabs\Pades\Signing\OpenSslSignerProvider;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Signing\SignerProviderInterface;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;

final readonly class PadesCmsSigner
{
    private SignatureCredentialInterface $signatureCredential;

    private SignerProviderInterface $signerProvider;

    public function __construct(
        PfxCertificate|SignatureCredentialInterface $certificate,
        private ?TimestampProviderInterface $timestampClient = null,
        ?SignerProviderInterface $signerProvider = null
    ) {
        $this->signatureCredential = $certificate instanceof PfxCertificate
            ? new PfxSignatureCredential($certificate)
            : $certificate;

        $this->signerProvider = $signerProvider ?? new OpenSslSignerProvider();
    }

    public function signPdfByteRangeData(
        string $data
    ): string {
        $signedAttributesForSignature = (new SignedAttributesBuilder())
            ->build(
                data: $data,
                certificatePem: $this->signatureCredential->getCertificatePem()
            );

        $signedAttributesForCms = Der::contextSpecificImplicitFromEncoded(
            0,
            $signedAttributesForSignature
        );

        $encryptedDigest = $this->signerProvider->sign(
            data: $signedAttributesForSignature,
            credential: $this->signatureCredential
        );

        $unsignedAttributes = [];

        if ($this->timestampClient !== null) {
            $timestampRequest = (new Rfc3161TimestampRequest())
                ->build($encryptedDigest);

            $timestampResponse = $this->timestampClient
                ->requestToken($timestampRequest);

            $timestampToken = (new TimestampResponseParser())
                ->extractToken($timestampResponse);

            $unsignedAttributes[] = (new SignatureTimestampTokenAttribute())
                ->build($timestampToken);
        }

        $unsignedAttributesForCms = $unsignedAttributes === []
            ? null
            : (new UnsignedAttributesBuilder())->build($unsignedAttributes);

        $signerInfo = (new SignerInfoBuilder())
            ->build(
                certificate: $this->signatureCredential,
                signedAttributesForCms: $signedAttributesForCms,
                encryptedDigest: $encryptedDigest,
                unsignedAttributesForCms: $unsignedAttributesForCms
            );

        $signedData = (new SignedDataBuilder())
            ->build(
                certificate: $this->signatureCredential,
                signerInfo: $signerInfo
            );


        return (new ContentInfoBuilder())
            ->build($signedData);
    }

    public function getCertificate(): PfxCertificate
    {
        if (! $this->signatureCredential instanceof PfxSignatureCredential) {
            throw new \RuntimeException(
                'A credencial configurada nao e um certificado PFX local.'
            );
        }

        return $this->signatureCredential->getPfxCertificate();
    }
}
