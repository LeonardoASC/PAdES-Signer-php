<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignaturePolicyQualifier
{
    private const ID_SPQ_ETS_URI_OID_HEX = '2A864886F70D0109100501';

    public function spuri(string $uri): string
    {
        return Der::sequence(
            Der::oid(self::ID_SPQ_ETS_URI_OID_HEX)
            . $this->ia5String($uri)
        );
    }

    private function ia5String(string $value): string
    {
        return "\x16" . Der::length(strlen($value)) . $value;
    }
}
