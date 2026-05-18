<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfSignaturePlaceholder
{
    public function findContentsRange(string $pdfContent): array
    {
        if (! preg_match('/\/Contents\s*<([0-9A-Fa-f]+)>/', $pdfContent, $matches, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException('Placeholder /Contents não encontrado no PDF.');
        }

        $hexStart = $matches[1][1];
        $hexLength = strlen($matches[1][0]);

        return [
            'start' => $hexStart,
            'end' => $hexStart + $hexLength,
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