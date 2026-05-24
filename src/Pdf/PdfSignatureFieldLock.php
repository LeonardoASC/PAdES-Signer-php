<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureFieldLock
{
    /**
     * @param array<string> $fieldNames
     */
    public function __construct(
        public string $action,
        public array $fieldNames = []
    ) {}
}
