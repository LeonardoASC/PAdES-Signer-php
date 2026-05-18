<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfSignatureExtractor
{
    public function extractHexSignature(string $pdfContent): string
    {
        if (! preg_match(
            '/\/Contents\s*<([0-9A-F]+)>/s',
            $pdfContent,
            $matches
        )) {
            throw new RuntimeException(
                'Assinatura hexadecimal não encontrada no PDF.'
            );
        }

        return $matches[1];
    }

    public function extractBinarySignature(string $pdfContent): string
    {
        $hex = $this->extractHexSignature($pdfContent);

        $binary = hex2bin($hex);

        if ($binary === false) {
            throw new RuntimeException(
                'Não foi possível converter assinatura hexadecimal.'
            );
        }

        return $binary;
    }
}