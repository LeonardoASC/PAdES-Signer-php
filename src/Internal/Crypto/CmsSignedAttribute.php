<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

final readonly class CmsSignedAttribute
{
    public function __construct(
        public string $oid,
        public string $encoded,
        public string $valuesEncoded,
        public string $valuesContent
    ) {}
}
