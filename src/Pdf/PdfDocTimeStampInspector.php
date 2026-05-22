<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfDocTimeStampInspector
{
    public function hasDocumentTimestamp(string $pdfContent): bool
    {
        return str_contains($pdfContent, '/SubFilter /ETSI.RFC3161');
    }

    public function documentTimestampCount(string $pdfContent): int
    {
        return preg_match_all('/\/SubFilter\s*\/ETSI\.RFC3161/', $pdfContent);
    }

    public function isLatestRevisionAfterDss(string $pdfContent): bool
    {
        $lastTimestamp = strrpos($pdfContent, '/SubFilter /ETSI.RFC3161');
        $lastDss = strrpos($pdfContent, '/Type /DSS');

        return $lastTimestamp !== false
            && $lastDss !== false
            && $lastTimestamp > $lastDss;
    }

    public function extractLatestDocumentTimestampToken(string $pdfContent): string
    {
        $position = strrpos($pdfContent, '/SubFilter /ETSI.RFC3161');

        if ($position !== false) {
            $tail = substr($pdfContent, $position);

            if (preg_match('/\/Contents\s*<([0-9A-F]+)>/s', $tail, $matches) === 1) {
                $hex = $matches[1];
                $binary = hex2bin($hex);

                if ($binary !== false) {
                    return rtrim($binary, "\x00");
                }
            }
        }

        if (preg_match_all('/\/Contents\s*<([0-9A-F]+)>/s', $pdfContent, $matches) !== false && $matches[1] !== []) {
            $hex = $matches[1][count($matches[1]) - 1];
            $binary = hex2bin($hex);

            if ($binary !== false) {
                return rtrim($binary, "\x00");
            }
        }

        throw new RuntimeException('DocTimeStamp RFC3161 nao encontrado no PDF.');
    }
}
