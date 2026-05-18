<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SigningCertificateV2
{
    public function build(string $certificatePem): string
    {
        $essCertIdV2 = (new EssCertIdV2())
            ->build($certificatePem);

        return Der::sequence($essCertIdV2);
    }

    public function attribute(string $certificatePem): string
    {
        return Der::sequence(
            Der::oid('2a864886f70d010910022f')
            . Der::set(
                $this->build($certificatePem)
            )
        );
    }
}