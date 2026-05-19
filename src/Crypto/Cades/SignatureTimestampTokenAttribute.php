<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignatureTimestampTokenAttribute
{
    public function build(string $timestampTokenDer): string
    {
        return Der::sequence(
            Der::oid('2a864886f70d010910020e')
            . Der::set($timestampTokenDer)
        );
    }
}