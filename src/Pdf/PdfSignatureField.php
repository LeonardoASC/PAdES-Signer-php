<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureField
{
    public function __construct(
        public int $objectNumber,
        public string $body,
        public ?string $name = null,
        public ?int $pageObjectNumber = null,
        public ?PdfSeedValueDictionary $seedValue = null,
        public ?PdfSignatureFieldLock $lock = null
    ) {}
}
