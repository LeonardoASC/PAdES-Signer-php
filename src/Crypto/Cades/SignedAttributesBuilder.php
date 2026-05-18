<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignedAttributesBuilder
{
    public function build(
        string $data,
        string $certificatePem
    ): string {
        $messageDigest = $this->messageDigestAttribute(
            $data
        );

        $signingCertificateV2 = (
            new SigningCertificateV2()
        )->attribute(
            $certificatePem
        );

        return Der::set(
            $messageDigest
            . $signingCertificateV2
        );
    }

    private function messageDigestAttribute(
        string $data
    ): string {
        $digest = hash(
            'sha256',
            $data,
            binary: true
        );

        return Der::sequence(
            Der::oid(
                '2a864886f70d010904'
            )
            . Der::set(
                Der::octetString(
                    $digest
                )
            )
        );
    }
}