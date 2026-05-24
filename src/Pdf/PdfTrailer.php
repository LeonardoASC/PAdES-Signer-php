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
        return (new PdfDictionaryReader())->getReference($this->dictionary, $name);
    }

    public function getInteger(string $name): ?int
    {
        return (new PdfDictionaryReader())->getInteger($this->dictionary, $name);
    }

    public function hasEntry(string $name): bool
    {
        return (new PdfDictionaryReader())->getValue($this->dictionary, $name) !== null;
    }

    public function firstDocumentIdHex(): ?string
    {
        if (! preg_match('/\/ID\s*\[\s*<([0-9A-Fa-f]+)>/', $this->dictionary, $matches)) {
            return null;
        }

        return strtolower($matches[1]);
    }
}
