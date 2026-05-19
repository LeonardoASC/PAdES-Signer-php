<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class UnsignedAttributesBuilder
{
    /**
     * @param array<string> $attributes
     */
    public function build(array $attributes): string
    {
        return Der::contextSpecificImplicitFromEncoded(
            1,
            Der::set(
                implode('', $attributes)
            )
        );
    }
}