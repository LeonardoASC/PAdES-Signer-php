<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfStructureValidator
{
    public function validate(string $pdfContent): PdfDocumentStructure
    {
        if (! str_starts_with($pdfContent, '%PDF-')) {
            throw new RuntimeException('Arquivo de entrada nao e um PDF valido.');
        }

        $structure = (new PdfStructuralParser())->parse($pdfContent);

        if ($structure->objects === []) {
            throw new RuntimeException('Nenhum objeto PDF encontrado.');
        }

        if ($structure->xrefTables === []) {
            throw new RuntimeException('xref table nao encontrada no PDF.');
        }

        if ($structure->trailers === []) {
            throw new RuntimeException('Trailer PDF nao encontrado.');
        }

        $this->validateXrefTables($structure);
        $this->validateTrailers($structure);

        return $structure;
    }

    private function validateXrefTables(PdfDocumentStructure $structure): void
    {
        foreach ($structure->xrefTables as $table) {
            foreach ($table as $objectNumber => $entry) {
                if (! $entry['in_use'] || $objectNumber === 0) {
                    continue;
                }

                if (! isset($structure->objects[$objectNumber])) {
                    throw new RuntimeException("xref referencia objeto ausente: {$objectNumber}.");
                }

                $object = $structure->objects[$objectNumber];

                if ($object->generation !== $entry['generation']) {
                    throw new RuntimeException("Geracao xref invalida para objeto {$objectNumber}.");
                }

                if ($object->offset !== $entry['offset']) {
                    throw new RuntimeException("Offset xref invalido para objeto {$objectNumber}.");
                }
            }
        }
    }

    private function validateTrailers(PdfDocumentStructure $structure): void
    {
        $contentLength = strlen($structure->content);

        foreach ($structure->trailers as $trailer) {
            if ($trailer->startXref < 0 || $trailer->startXref >= $contentLength) {
                throw new RuntimeException('startxref aponta para fora do PDF.');
            }

            if (substr($structure->content, $trailer->startXref, 4) !== 'xref') {
                throw new RuntimeException('startxref nao aponta para uma xref table.');
            }

            $root = $trailer->getReference('Root');

            if ($root === null) {
                throw new RuntimeException('/Root nao encontrado no trailer do PDF.');
            }

            [$rootObject] = array_map('intval', explode(' ', $root));

            if (! isset($structure->objects[$rootObject])) {
                throw new RuntimeException('/Root referencia objeto ausente.');
            }

            $size = $trailer->getInteger('Size');

            if ($size !== null && $size <= $structure->highestObjectNumber()) {
                throw new RuntimeException('/Size do trailer e menor que a tabela de objetos.');
            }
        }
    }
}
