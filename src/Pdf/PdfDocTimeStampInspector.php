<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampTokenParser;
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

    public function latestTimestampCoversLatestRevision(string $pdfContent): bool
    {
        try {
            $byteRange = $this->extractLatestDocumentTimestampByteRange($pdfContent);
        } catch (RuntimeException) {
            return false;
        }

        return $byteRange->start1 === 0
            && $byteRange->start2 + $byteRange->length2 === strlen($pdfContent)
            && $byteRange->start1 + $byteRange->length1 < $byteRange->start2;
    }

    public function archivalTimestampChainIsOrdered(string $pdfContent): bool
    {
        $tokens = $this->extractDocumentTimestampTokens($pdfContent);

        if ($tokens === []) {
            return false;
        }

        $previous = null;
        $parser = new Rfc3161TimestampTokenParser();

        foreach ($tokens as $token) {
            try {
                $time = $parser->parseToken($token)->genTime;
            } catch (RuntimeException) {
                return false;
            }

            if ($previous !== null && $time < $previous) {
                return false;
            }

            $previous = $time;
        }

        return true;
    }

    public function extractLatestDocumentTimestampSignedData(string $pdfContent): string
    {
        return (new ByteRangeCalculator())->extractSignedData(
            $pdfContent,
            $this->extractLatestDocumentTimestampByteRange($pdfContent)
        );
    }

    public function extractLatestDocumentTimestampByteRange(string $pdfContent): ByteRange
    {
        $tail = $this->latestTimestampTail($pdfContent);

        if (preg_match('/\/ByteRange\s*\[(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\]/', $tail, $matches) !== 1) {
            throw new RuntimeException('ByteRange do DocTimeStamp RFC3161 nao encontrado no PDF.');
        }

        return new ByteRange(
            start1: (int) $matches[1],
            length1: (int) $matches[2],
            start2: (int) $matches[3],
            length2: (int) $matches[4]
        );
    }

    public function extractLatestDocumentTimestampToken(string $pdfContent): string
    {
        try {
            $tail = $this->latestTimestampTail($pdfContent);

            if (preg_match('/\/Contents\s*<([0-9A-F]+)>/s', $tail, $matches) === 1) {
                $hex = $matches[1];
                $binary = hex2bin($hex);

                if ($binary !== false) {
                    return rtrim($binary, "\x00");
                }
            }
        } catch (RuntimeException) {
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

    /**
     * @return array<string>
     */
    public function extractDocumentTimestampTokens(string $pdfContent): array
    {
        $tokens = [];
        $offset = 0;

        while (($position = strpos($pdfContent, '/SubFilter /ETSI.RFC3161', $offset)) !== false) {
            $objectEnd = strpos($pdfContent, 'endobj', $position);
            $tail = $objectEnd === false
                ? substr($pdfContent, $position)
                : substr($pdfContent, $position, $objectEnd - $position);
            $offset = $position + 1;

            if (preg_match('/\/Contents\s*<([0-9A-F]+)>/s', $tail, $matches) !== 1) {
                continue;
            }

            $hex = $matches[1];
            $binary = hex2bin($hex);

            if ($binary !== false) {
                $tokens[] = rtrim($binary, "\x00");
            }
        }

        return $tokens;
    }

    private function latestTimestampTail(string $pdfContent): string
    {
        $position = strrpos($pdfContent, '/SubFilter /ETSI.RFC3161');

        if ($position === false) {
            $position = strrpos($pdfContent, '/SubFilter/ETSI.RFC3161');
        }

        if ($position === false) {
            throw new RuntimeException('DocTimeStamp RFC3161 nao encontrado no PDF.');
        }

        return substr($pdfContent, $position);
    }
}
