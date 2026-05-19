<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class OcspRevocationInfoArchivalAttribute
{
    /**
     * @param array<string> $ocspResponsesDer
     */
    public function build(
        array $ocspResponsesDer
    ): string {
        $responses = '';

        foreach ($ocspResponsesDer as $response) {
            $responses .= Der::octetString(
                $response
            );
        }

        return Der::sequence(
            Der::oid('2a864886f70d0109100224')
            . Der::set(
                Der::sequence($responses)
            )
        );
    }
}