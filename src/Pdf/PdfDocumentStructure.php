<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfDocumentStructure
{
    /**
     * @param array<int, PdfIndirectObject> $objects
     * @param array<int, array<int, array{offset:int,generation:int,in_use:bool,type?:int,object_stream?:int,index?:int}>> $xrefTables
     * @param array<int, PdfTrailer> $trailers
     */
    public function __construct(
        public string $content,
        public array $objects,
        public array $xrefTables,
        public array $trailers
    ) {}

    public function latestTrailer(): PdfTrailer
    {
        $trailer = $this->trailers[count($this->trailers) - 1] ?? null;

        if (! $trailer instanceof PdfTrailer) {
            throw new RuntimeException('Trailer PDF nao encontrado.');
        }

        return $trailer;
    }

    public function highestObjectNumber(): int
    {
        if ($this->objects === []) {
            throw new RuntimeException('Nenhum objeto PDF encontrado.');
        }

        return max(array_keys($this->objects));
    }

    public function getObject(int $number): PdfIndirectObject
    {
        if (! isset($this->objects[$number])) {
            throw new RuntimeException("Objeto PDF {$number} nao encontrado.");
        }

        return $this->objects[$number];
    }
}
