<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfSignaturePageAppender
{
    /**
     * @param array{0:int|float,1:int|float,2:int|float,3:int|float} $mediaBox
     * @return array{pageTreeObjects:array<int,string>,pageBody:string}
     */
    public function append(
        PdfDocumentStructure $structure,
        string $catalogBody,
        int $pageObjectNumber,
        int $widgetObjectNumber,
        array $mediaBox = [0, 0, 595, 842]
    ): array {
        $rootPagesObjectNumber = $this->pagesObjectNumber($catalogBody);
        $targetPath = $this->targetPagesPath($structure, $rootPagesObjectNumber);
        $targetPagesObjectNumber = $targetPath[count($targetPath) - 1];
        $targetPagesBody = $structure->getObject($targetPagesObjectNumber)->body;
        $kidsReference = $this->indirectKidsReference($targetPagesBody);

        $updatedObjects = [];

        if ($kidsReference !== null) {
            $kidsObject = $structure->getObjectByReference($kidsReference);
            $updatedObjects[$kidsObject->number] = $this->appendReferenceToArrayBody(
                arrayBody: $kidsObject->body,
                objectNumber: $pageObjectNumber
            );
            $updatedObjects[$targetPagesObjectNumber] = $targetPagesBody;
        } else {
            $updatedObjects[$targetPagesObjectNumber] = (new PdfDictionaryUpdater())
                ->appendReferenceToArray(
                    dictionary: $targetPagesBody,
                    name: 'Kids',
                    objectNumber: $pageObjectNumber
                );
        }

        foreach (array_reverse($targetPath) as $pagesObjectNumber) {
            $body = $updatedObjects[$pagesObjectNumber]
                ?? $structure->getObject($pagesObjectNumber)->body;

            $updatedObjects[$pagesObjectNumber] = $this->incrementPageCount($body);
        }

        $this->validatePageCountIncrement($structure, $updatedObjects, $rootPagesObjectNumber);

        return [
            'pageTreeObjects' => $updatedObjects,
            'pageBody' => $this->pageBody(
                pagesObjectNumber: $targetPagesObjectNumber,
                widgetObjectNumber: $widgetObjectNumber,
                mediaBox: $mediaBox
            ),
        ];
    }

    private function pagesObjectNumber(string $catalogBody): int
    {
        $reference = (new PdfDictionaryReader())->getReference($catalogBody, 'Pages');

        if ($reference === null) {
            throw new RuntimeException('/Pages nao encontrado no catalogo PDF.');
        }

        return (int) explode(' ', $reference)[0];
    }

    /**
     * @return array<int>
     */
    private function targetPagesPath(PdfDocumentStructure $structure, int $rootPagesObjectNumber): array
    {
        $path = [$rootPagesObjectNumber];
        $currentObjectNumber = $rootPagesObjectNumber;
        $seen = [];

        while (true) {
            if (isset($seen[$currentObjectNumber])) {
                throw new RuntimeException('Arvore /Pages circular.');
            }

            $seen[$currentObjectNumber] = true;
            $body = $structure->getObject($currentObjectNumber)->body;
            $kids = $this->resolvedKidReferences($structure, $body);
            $nextPagesObjectNumber = null;

            foreach (array_reverse($kids) as $kidReference) {
                $kidObject = $structure->getObjectByReference($kidReference);

                if ((new PdfDictionaryReader())->hasNameValue($kidObject->body, 'Type', 'Pages')) {
                    $nextPagesObjectNumber = $kidObject->number;
                    break;
                }
            }

            if ($nextPagesObjectNumber === null) {
                return $path;
            }

            $path[] = $nextPagesObjectNumber;
            $currentObjectNumber = $nextPagesObjectNumber;
        }
    }

    private function incrementPageCount(string $pagesBody): string
    {
        $updated = preg_replace_callback(
            '/\/Count\s+(\d+)\b/s',
            static fn (array $matches): string => '/Count ' . ((int) $matches[1] + 1),
            $pagesBody,
            1,
            $replacements
        );

        if ($updated === null || $replacements !== 1) {
            throw new RuntimeException('/Count nao encontrado na arvore de paginas PDF.');
        }

        return $updated;
    }

    /**
     * @return array<string>
     */
    private function kidReferences(string $pagesBody): array
    {
        $kids = trim((new PdfDictionaryReader())->getValue($pagesBody, 'Kids') ?? '');

        if (preg_match('/^\d+\s+\d+\s+R$/', $kids) === 1) {
            throw new RuntimeException('/Kids indireto precisa ser resolvido pelo chamador.');
        }

        if (! str_starts_with($kids, '[') || ! str_ends_with($kids, ']')) {
            throw new RuntimeException('/Kids da arvore de paginas nao e um array direto.');
        }

        if (! preg_match_all('/\d+\s+\d+\s+R\b/', $kids, $matches)) {
            return [];
        }

        return $matches[0];
    }

    private function indirectKidsReference(string $pagesBody): ?string
    {
        $kids = trim((new PdfDictionaryReader())->getValue($pagesBody, 'Kids') ?? '');

        return preg_match('/^\d+\s+\d+\s+R$/', $kids) === 1
            ? $kids
            : null;
    }

    /**
     * @return array<string>
     */
    private function resolvedKidReferences(PdfDocumentStructure $structure, string $pagesBody): array
    {
        $kidsReference = $this->indirectKidsReference($pagesBody);

        if ($kidsReference !== null) {
            return $this->referencesFromArrayBody(
                $structure->getObjectByReference($kidsReference)->body
            );
        }

        return $this->kidReferences($pagesBody);
    }

    /**
     * @return array<string>
     */
    private function referencesFromArrayBody(string $arrayBody): array
    {
        $arrayBody = trim($arrayBody);

        if (! str_starts_with($arrayBody, '[') || ! str_ends_with($arrayBody, ']')) {
            throw new RuntimeException('Objeto /Kids indireto nao contem array direto.');
        }

        if (! preg_match_all('/\d+\s+\d+\s+R\b/', $arrayBody, $matches)) {
            return [];
        }

        return $matches[0];
    }

    private function appendReferenceToArrayBody(string $arrayBody, int $objectNumber): string
    {
        $arrayBody = trim($arrayBody);

        if (! str_starts_with($arrayBody, '[') || ! str_ends_with($arrayBody, ']')) {
            throw new RuntimeException('Objeto /Kids indireto nao contem array direto.');
        }

        $content = trim(substr($arrayBody, 1, -1));
        $reference = "{$objectNumber} 0 R";

        if (preg_match('/(?<!\d)' . preg_quote((string) $objectNumber, '/') . '\s+0\s+R\b/', $content) === 1) {
            return $arrayBody;
        }

        return '[' . ($content === '' ? '' : $content . ' ') . $reference . ']';
    }

    /**
     * @param array<int,string> $updatedObjects
     */
    private function validatePageCountIncrement(
        PdfDocumentStructure $structure,
        array $updatedObjects,
        int $rootPagesObjectNumber
    ): void {
        $before = $this->pageCount($structure->getObject($rootPagesObjectNumber)->body);
        $after = $this->pageCount($updatedObjects[$rootPagesObjectNumber] ?? '');

        if ($after !== $before + 1) {
            throw new RuntimeException('/Count da arvore de paginas nao foi atualizado corretamente.');
        }
    }

    private function pageCount(string $pagesBody): int
    {
        $count = (new PdfDictionaryReader())->getInteger($pagesBody, 'Count');

        if ($count === null) {
            throw new RuntimeException('/Count nao encontrado na arvore de paginas PDF.');
        }

        return $count;
    }

    /**
     * @param array{0:int|float,1:int|float,2:int|float,3:int|float} $mediaBox
     */
    private function pageBody(
        int $pagesObjectNumber,
        int $widgetObjectNumber,
        array $mediaBox
    ): string {
        return "<<\n"
            . "/Type /Page\n"
            . "/Parent {$pagesObjectNumber} 0 R\n"
            . "/MediaBox [ " . $this->formatNumbers($mediaBox) . " ]\n"
            . "/Annots [{$widgetObjectNumber} 0 R]\n"
            . ">>";
    }

    /**
     * @param array<int|float> $numbers
     */
    private function formatNumbers(array $numbers): string
    {
        return implode(
            ' ',
            array_map(
                static fn (int|float $value): string => is_float($value)
                    ? rtrim(rtrim(sprintf('%.6F', $value), '0'), '.')
                    : (string) $value,
                $numbers
            )
        );
    }
}
