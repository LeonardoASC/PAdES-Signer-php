<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfDocumentStructure
{
    private const int MAX_REFERENCE_DEPTH = 32;

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

    public function getObjectByReference(string $reference): PdfIndirectObject
    {
        if (! preg_match('/^\s*(\d+)\s+(\d+)\s+R\s*$/', $reference, $matches)) {
            throw new RuntimeException("Referencia indireta PDF invalida: {$reference}");
        }

        return $this->getObject((int) $matches[1]);
    }

    public function resolveObject(int $number, int $maxDepth = self::MAX_REFERENCE_DEPTH): PdfIndirectObject
    {
        $seen = [];
        $object = $this->getObject($number);

        for ($depth = 0; $depth <= $maxDepth; $depth++) {
            if (isset($seen[$object->number])) {
                throw new RuntimeException('Referencia indireta PDF circular.');
            }

            $seen[$object->number] = true;

            if (! preg_match('/^\s*(\d+)\s+\d+\s+R\s*$/', $object->body, $matches)) {
                return $object;
            }

            $object = $this->getObject((int) $matches[1]);
        }

        throw new RuntimeException('Limite de recursao de referencia indireta PDF excedido.');
    }

    public function resolveReference(string $reference, int $maxDepth = self::MAX_REFERENCE_DEPTH): PdfIndirectObject
    {
        return $this->resolveObject(
            number: $this->getObjectByReference($reference)->number,
            maxDepth: $maxDepth
        );
    }
}
