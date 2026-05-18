<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignerInfoBuilder
{
    public function build(
        PfxCertificate $certificate,
        string $signedAttributes,
        string $encryptedDigest
    ): string {
        return Der::sequence(
            Der::integer(1)
            . $this->sid($certificate)
            . $this->digestAlgorithm()
            . Der::contextSpecificConstructed(0, $signedAttributes)
            . $this->signatureAlgorithm()
            . Der::octetString($encryptedDigest)
        );
    }

    private function sid(PfxCertificate $certificate): string
    {
        $info = $certificate->getInfo();

        $serial = (int) ($info['serialNumber'] ?? 1);

        return Der::sequence(
            $this->issuerNamePlaceholder()
            . Der::integer($serial)
        );
    }

    private function issuerNamePlaceholder(): string
    {
        return Der::sequence(
            Der::set(
                Der::sequence(
                    Der::oid('550403')
                    . Der::octetString('Unknown Issuer')
                )
            )
        );
    }

    private function digestAlgorithm(): string
    {
        return Der::sequence(
            Der::oid('608648016503040201')
            . Der::null()
        );
    }

    private function signatureAlgorithm(): string
    {
        return Der::sequence(
            Der::oid('2a864886f70d010101')
            . Der::null()
        );
    }
}