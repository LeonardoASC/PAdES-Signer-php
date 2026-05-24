<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

final readonly class CmsSignerCertificateInfo
{
    public function __construct(
        public string $signerIdentifierDer,
        public ?string $certificateDer,
        public ?string $certificatePem,
        public bool $matchesSignerInfo
    ) {}
}
