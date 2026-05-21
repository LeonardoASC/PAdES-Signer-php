<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfIndirectObject
{
    public function __construct(
        public int $number,
        public int $generation,
        public int $offset,
        public string $body
    ) {}

    public function reference(): string
    {
        return "{$this->number} {$this->generation} R";
    }
}
