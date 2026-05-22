<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignedAttributesBuilder
{
    public function build(
        string $data,
        string $certificatePem,
        SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy(),
        bool $includeSigningTime = false
    ): string {
        $attributes = [
            $this->contentTypeAttribute(),
            $this->messageDigestAttribute($data, $algorithmPolicy),
            (new SigningCertificateV2())->attribute($certificatePem),
        ];

        if ($includeSigningTime) {
            $attributes[] = $this->signingTimeAttribute();
        }

        usort(
            $attributes,
            static function (string $left, string $right): int {
                return strcmp(
                    bin2hex($left),
                    bin2hex($right)
                );
            }
        );

        return Der::set(
            implode('', $attributes)
        );
    }

    private function contentTypeAttribute(): string
    {
        return Der::sequence(
            Der::oid('2a864886f70d010903')
                . Der::set(
                    Der::oid('2a864886f70d010701')
                )
        );
    }

    private function signingTimeAttribute(): string
    {
        return Der::sequence(
            Der::oid('2a864886f70d010905')
                . Der::set(
                    Der::utcTime(gmdate('ymdHis') . 'Z')
                )
        );
    }

    private function messageDigestAttribute(
        string $data,
        SignatureAlgorithmPolicy $algorithmPolicy
    ): string {
        return Der::sequence(
            Der::oid('2a864886f70d010904')
                . Der::set(
                    Der::octetString(
                        $algorithmPolicy->hash($data)
                    )
                )
        );
    }
}
