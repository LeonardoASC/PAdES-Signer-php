<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfTrailer
{
    public function __construct(
        public int $offset,
        public string $dictionary,
        public int $startXref
    ) {}

    public function getReference(string $name): ?string
    {
        if (! preg_match('/\/' . preg_quote($name, '/') . '\s+(\d+\s+\d+\s+R)\b/', $this->dictionary, $matches)) {
            return null;
        }

        return $matches[1];
    }

    public function getInteger(string $name): ?int
    {
        if (! preg_match('/\/' . preg_quote($name, '/') . '\s+(\d+)\b/', $this->dictionary, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    public function firstDocumentIdHex(): ?string
    {
        if (! preg_match('/\/ID\s*\[\s*<([0-9A-Fa-f]+)>/', $this->dictionary, $matches)) {
            return null;
        }

        return strtolower($matches[1]);
    }
}
