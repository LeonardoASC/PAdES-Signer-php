<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfByteRangePlaceholder
{
    public function findRange(string $pdfContent): array
    {
        if (! preg_match('/\/ByteRange\s*\[\s*(\*{10})\s+(\*{10})\s+(\*{10})\s+(\*{10})\s*\]/', $pdfContent, $matches, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException('Placeholder /ByteRange não encontrado no PDF.');
        }

        return [
            'start' => $matches[0][1],
            'end' => $matches[0][1] + strlen($matches[0][0]),
            'content' => $matches[0][0],
        ];
    }

    public function replace(
        string $pdfContent,
        ByteRange $byteRange
    ): string {
        $range = $this->findRange($pdfContent);

        $replacement = '/ByteRange ' . $byteRange->toPdfArray();

        if (strlen($replacement) > strlen($range['content'])) {
            throw new RuntimeException('ByteRange excede o espaço reservado no PDF.');
        }

        $replacement = str_pad($replacement, strlen($range['content']), ' ');

        return substr_replace(
            $pdfContent,
            $replacement,
            $range['start'],
            strlen($range['content'])
        );
    }
}