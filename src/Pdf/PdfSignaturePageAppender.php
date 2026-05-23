<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfSignaturePageAppender
{
    /**
     * @param array{0:int|float,1:int|float,2:int|float,3:int|float} $mediaBox
     * @return array{pagesObjectNumber:int,pagesBody:string,pageBody:string}
     */
    public function append(
        PdfDocumentStructure $structure,
        string $catalogBody,
        int $pageObjectNumber,
        int $widgetObjectNumber,
        array $mediaBox = [0, 0, 595, 842]
    ): array {
        $pagesObjectNumber = $this->pagesObjectNumber($catalogBody);
        $pagesBody = $structure->getObject($pagesObjectNumber)->body;

        $updatedPagesBody = (new PdfDictionaryUpdater())
            ->appendReferenceToArray(
                dictionary: $pagesBody,
                name: 'Kids',
                objectNumber: $pageObjectNumber
            );

        $updatedPagesBody = $this->incrementPageCount($updatedPagesBody);

        return [
            'pagesObjectNumber' => $pagesObjectNumber,
            'pagesBody' => $updatedPagesBody,
            'pageBody' => $this->pageBody(
                pagesObjectNumber: $pagesObjectNumber,
                widgetObjectNumber: $widgetObjectNumber,
                mediaBox: $mediaBox
            ),
        ];
    }

    private function pagesObjectNumber(string $catalogBody): int
    {
        if (! preg_match('/\/Pages\s+(\d+)\s+\d+\s+R\b/s', $catalogBody, $matches)) {
            throw new RuntimeException('/Pages nao encontrado no catalogo PDF.');
        }

        return (int) $matches[1];
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
            . "/Resources <<\n"
            . ">>\n"
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
