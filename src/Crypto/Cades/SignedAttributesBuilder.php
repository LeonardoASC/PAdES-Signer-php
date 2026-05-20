<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignedAttributesBuilder
{
    public function build(
        string $data,
        string $certificatePem,
        ?IcpBrasilSignaturePolicy $signaturePolicy = null
    ): string {
        $attributes = [
            $this->contentTypeAttribute(),
            $this->signingTimeAttribute(),
            $this->messageDigestAttribute($data),
            (new SigningCertificateV2())->attribute($certificatePem),
        ];

        if ($signaturePolicy !== null) {
            $attributes[] = (new SignaturePolicyIdentifierAttribute())
                ->build($signaturePolicy);
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
