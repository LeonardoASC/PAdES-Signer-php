<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignedAttributesBuilder
{
    public function build(
        string $data,
        string $certificatePem
    ): string {
        $attributes = [
            $this->contentTypeAttribute(),
            $this->signingTimeAttribute(),
            $this->messageDigestAttribute($data),
            (new SigningCertificateV2())->attribute($certificatePem),
        ];

        usort(
            $attributes,
            static fn (string $left, string $right): int => strcmp($left, $right)
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
        string $data
    ): string {
        return Der::sequence(
            Der::oid('2a864886f70d010904')
                . Der::set(
                    Der::octetString(
                        hash('sha256', $data, binary: true)
                    )
                )
        );
    }
}
