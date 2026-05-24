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

                if (($entry['type'] ?? 1) === 2) {
                    $objectStream = $entry['object_stream'] ?? null;

                    if (! is_int($objectStream) || ! isset($structure->objects[$objectStream])) {
                        throw new RuntimeException("xref stream referencia object stream ausente: {$objectNumber}.");
                    }

                    if (! isset($structure->objects[$objectNumber])) {
                        throw new RuntimeException("object stream nao contem objeto referenciado: {$objectNumber}.");
                    }

                    continue;
                }

                $header = substr($structure->content, $entry['offset'], 64);

                if (! preg_match(
                    '/^' . preg_quote((string) $objectNumber, '/') . '\s+'
                        . preg_quote((string) $entry['generation'], '/') . '\s+obj\b/',
                    $header
                )) {
                    throw new RuntimeException("Offset xref invalido para objeto {$objectNumber}.");
                }
            }
        }
    }

    private function validateTrailers(PdfDocumentStructure $structure): void
    {
        $contentLength = strlen($structure->content);

        foreach ($structure->trailers as $trailer) {
            if ($trailer->hasEntry('Encrypt')) {
                throw new RuntimeException('PDF criptografado nao e suportado.');
            }

            if ($trailer->startXref < 0 || $trailer->startXref >= $contentLength) {
                throw new RuntimeException('startxref aponta para fora do PDF.');
            }

            if (! $this->startXrefPointsToXref($structure, $trailer->startXref)) {
                throw new RuntimeException('startxref nao aponta para uma xref table ou xref stream.');
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
            $highestObjectBeforeTrailer = $this->highestObjectBeforeOffset(
                $structure,
                $trailer->offset
            );

            if ($size !== null && $size <= $highestObjectBeforeTrailer) {
                throw new RuntimeException('/Size do trailer e menor que a tabela de objetos.');
            }
        }
    }

    private function highestObjectBeforeOffset(
        PdfDocumentStructure $structure,
        int $offset
    ): int {
        $highest = 0;

        foreach ($structure->objects as $object) {
            if ($object->offset < $offset) {
                $highest = max($highest, $object->number);
            }
        }

        return $highest;
    }

    private function startXrefPointsToXref(
        PdfDocumentStructure $structure,
        int $startXref
    ): bool {
        if (substr($structure->content, $startXref, 4) === 'xref') {
            return true;
        }

        foreach ($structure->objects as $object) {
            if ($object->offset === $startXref && str_contains($object->body, '/Type /XRef')) {
                return true;
            }
        }

        return false;
    }
}
