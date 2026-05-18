<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class IssuerAndSerialNumber
{
    public function build(PfxCertificate $certificate): string
    {
        $info = $certificate->getInfo();

        $serial = (int) ($info['serialNumber'] ?? 1);

        return Der::sequence(
            $this->issuerName($info['issuer'] ?? [])
            . Der::integer($serial)
        );
    }

    /**
     * @param array<string, string> $issuer
     */
    private function issuerName(array $issuer): string
    {
        $commonName = $issuer['CN'] ?? 'Unknown Issuer';

        return Der::sequence(
            Der::set(
                Der::sequence(
                    Der::oid('550403')
                    . Der::octetString($commonName)
                )
            )
        );
    }
}