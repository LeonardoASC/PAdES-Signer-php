<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class RevocationRefsAttribute
{
    /**
     * @param array<string> $ocspResponsesDer
     * @param array<string> $crlsDer
     */
    public function build(
        array $ocspResponsesDer = [],
        array $crlsDer = []
    ): string {
        $refs = '';

        foreach ($ocspResponsesDer as $response) {
            $refs .= $this->reference($response);
        }

        foreach ($crlsDer as $crl) {
            $refs .= $this->reference($crl);
        }

        return Der::sequence(
            Der::oid('2a864886f70d0109100216')
            . Der::set(
                Der::sequence($refs)
            )
        );
    }

    private function reference(string $content): string
    {
        return Der::sequence(
            $this->digestAlgorithm()
            . Der::octetString(
                hash('sha256', $content, binary: true)
            )
        );
    }

    private function digestAlgorithm(): string
    {
        return Der::sha256AlgorithmIdentifier();
    }
}
