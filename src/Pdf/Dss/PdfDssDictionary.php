<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf\Dss;

final readonly class PdfDssDictionary
{
    /**
     * @param array<string> $certReferences
     * @param array<string> $ocspReferences
     * @param array<string> $crlReferences
     */
    public function build(
        array $certReferences = [],
        array $ocspReferences = [],
        array $crlReferences = [],
        string $vriDictionary = "<<\n>>"
    ): string {
        return "<<\n"
            . "/Type /DSS\n"
            . "/Certs " . $this->formatReferences($certReferences) . "\n"
            . "/OCSPs " . $this->formatReferences($ocspReferences) . "\n"
            . "/CRLs " . $this->formatReferences($crlReferences) . "\n"
            . "/VRI " . $vriDictionary . "\n"
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
