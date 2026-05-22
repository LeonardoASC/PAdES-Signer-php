<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfSignaturePlaceholder
{
    public function findContentsRange(string $pdfContent): array
    {
        if (! preg_match_all('/\/Contents\s*<([0-9A-Fa-f]+)>/', $pdfContent, $matches, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException('Placeholder /Contents nao encontrado no PDF.');
        }

        $selected = null;

        foreach ($matches[1] as $match) {
            if (preg_match('/^0+$/', $match[0]) === 1) {
                $selected = $match;
            }
        }

        if ($selected === null) {
            throw new RuntimeException('Placeholder /Contents nao encontrado no PDF.');
        }

        $hexStart = $selected[1];
        $hexLength = strlen($selected[0]);

        return [
            'start' => $hexStart,
            'end' => $hexStart + $hexLength,
        ];
    }

    /**
     * @return array{start:int,end:int}
     */
    public function findContentsObjectRange(string $pdfContent): array
    {
        $range = $this->findContentsRange($pdfContent);

        $objectStart = $range['start'] - 1;
        $objectEnd = $range['end'] + 1;

        if (
            $objectStart < 0
            || ! isset($pdfContent[$objectStart], $pdfContent[$objectEnd - 1])
            || $pdfContent[$objectStart] !== '<'
            || $pdfContent[$objectEnd - 1] !== '>'
        ) {
            throw new RuntimeException('Delimitadores do /Contents nao encontrados no PDF.');
        }

        return [
            'start' => $objectStart,
            'end' => $objectEnd,
        ];
    }

    public function replaceContents(
        string $pdfContent,
        string $hexSignature
    ): string {
        $range = $this->findContentsRange($pdfContent);

        $currentLength = $range['end'] - $range['start'];

        if (strlen($hexSignature) !== $currentLength) {
            throw new RuntimeException('A assinatura precisa ter exatamente o tamanho do placeholder.');
        }

        return substr_replace(
            $pdfContent,
            $hexSignature,
            $range['start'],
            $currentLength
        );
    }
}
