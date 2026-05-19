<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class OcspEmbeddedResponseAttribute
{
    public function build(
        string $ocspResponseDer
    ): string {
        return Der::sequence(
            Der::oid('2a864886f70d0109100204')
            . Der::set(
                Der::octetString(
                    $ocspResponseDer
                )
            )
        );
    }
}