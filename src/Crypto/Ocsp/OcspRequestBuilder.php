<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class OcspRequestBuilder
{
    public function build(
        string $issuerNameDer,
        string $issuerPublicKeyDer,
        string $serialNumberHex
    ): string {
        $certId = $this->certId(
            $issuerNameDer,
            $issuerPublicKeyDer,
            $serialNumberHex
        );

        $request = Der::sequence(
            Der::sequence(
                Der::sequence($certId)
            )
        );

        return Der::sequence($request);
    }

    private function certId(
        string $issuerNameDer,
        string $issuerPublicKeyDer,
        string $serialNumberHex
    ): string {
        return Der::sequence(
            $this->sha1AlgorithmIdentifier()
            . Der::octetString(
                sha1($issuerNameDer, true)
            )
            . Der::octetString(
                sha1($issuerPublicKeyDer, true)
            )
            . Der::integerFromHex(
                $serialNumberHex
            )
        );
    }

    private function sha1AlgorithmIdentifier(): string
    {
        return Der::sequence(
            Der::oid('2b0e03021a')
            . Der::null()
        );
    }
}