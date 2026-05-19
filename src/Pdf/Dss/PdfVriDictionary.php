<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf\Dss;

use InvalidArgumentException;

final readonly class PdfVriDictionary
{
    /**
     * @param array<string> $certReferences
     * @param array<string> $ocspReferences
     * @param array<string> $crlReferences
     */
    public function build(
        string $signatureHash,
        array $certReferences = [],
        array $ocspReferences = [],
        array $crlReferences = []
    ): string {
        $signatureHash = strtoupper($signatureHash);

        if (! preg_match('/^[0-9A-F]+$/', $signatureHash)) {
            throw new InvalidArgumentException('VRI signature hash must be hexadecimal.');
        }

        return "<<\n"
            . "/{$signatureHash} <<\n"
            . "/Cert " . $this->formatReferences($certReferences) . "\n"
            . "/OCSP " . $this->formatReferences($ocspReferences) . "\n"
            . "/CRL " . $this->formatReferences($crlReferences) . "\n"
            . ">>\n"
            . ">>";
    }

    /**
     * @param array<string> $references
     */
    private function formatReferences(array $references): string
    {
        if ($references === []) {
            return '[]';
        }

        return '[' . implode(' ', $references) . ']';
    }
}
