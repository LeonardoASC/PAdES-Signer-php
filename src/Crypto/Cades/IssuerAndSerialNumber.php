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

        return Der::sequence(
            $this->issuerName($info['issuer'] ?? [])
                . Der::integerFromHex(
                    $certificate->getSerialNumberHex()
                )
        );
    }

    /**
     * @param array<string, string> $issuer
     */
    private function issuerName(array $issuer): string
    {
        $attributes = '';

        $map = [
            'C' => ['550406', 'printable'],
            'ST' => ['550408', 'utf8'],
            'L' => ['550407', 'utf8'],
            'O' => ['55040A', 'utf8'],
            'OU' => ['55040B', 'utf8'],
            'CN' => ['550403', 'utf8'],
        ];

        foreach ($map as $key => [$oid, $type]) {
            if (! isset($issuer[$key])) {
                continue;
            }

            $value = $issuer[$key];

            $encodedValue = $type === 'printable'
                ? Der::printableString($value)
                : Der::utf8String($value);

            $attributes .= Der::set(
                Der::sequence(
                    Der::oid($oid)
                        . $encodedValue
                )
            );
        }

        return Der::sequence($attributes);
    }
}
