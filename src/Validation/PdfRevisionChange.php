<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

final readonly class PdfRevisionChange
{
    public function __construct(
        public int $objectNumber,
        public int $offset,
        public string $classification,
        public bool $allowed,
        public bool $redefinesSignedObject,
        public string $reason
    ) {}
}
