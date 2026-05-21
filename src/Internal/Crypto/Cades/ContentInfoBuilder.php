<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class ContentInfoBuilder
{
    public function build(string $signedData): string
    {
        return Der::sequence(
            Der::oid('2a864886f70d010702')
            . Der::contextSpecificConstructed(
                0,
                $signedData
            )
        );
    }
}
