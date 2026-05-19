<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class CertificateRefsAttribute
{
    /**
     * @param array<string> $certificatesDer
     */
    public function build(array $certificatesDer): string
    {
        $refs = '';

        foreach ($certificatesDer as $certificateDer) {
            $refs .= $this->certificateRef($certificateDer);
        }

        return Der::sequence(
            Der::oid('2a864886f70d0109100215')
            . Der::set(
                Der::sequence($refs)
            )
        );
    }

    private function certificateRef(string $certificateDer): string
    {
        return Der::sequence(
            $this->digestAlgorithm()
            . Der::octetString(
                hash('sha256', $certificateDer, binary: true)
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
}