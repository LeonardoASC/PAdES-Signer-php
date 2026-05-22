<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf\Dss;

final readonly class PdfDssInspector
{
    /**
     * @return array<string, bool|string|null>
     */
    public function inspect(string $pdfContent, ?string $expectedVriHash = null): array
    {
        $hasDssReference = preg_match('/\/DSS\s+\d+\s+0\s+R/', $pdfContent) === 1;
        $hasDssDictionary = str_contains($pdfContent, '/Type /DSS');
        $hasCerts = preg_match('/\/Certs\s*\[\s*\d+\s+0\s+R/s', $pdfContent) === 1;
        $hasOcsp = preg_match('/\/OCSPs\s*\[\s*\d+\s+0\s+R/s', $pdfContent) === 1;
        $hasCrl = preg_match('/\/CRLs\s*\[\s*\d+\s+0\s+R/s', $pdfContent) === 1;
        $vriHash = $this->firstVriHash($pdfContent);

        return [
            'has_dss_reference' => $hasDssReference,
            'has_dss_dictionary' => $hasDssDictionary,
            'has_vri_dictionary' => $vriHash !== null,
            'has_certificates' => $hasCerts,
            'has_ocsp_responses' => $hasOcsp,
            'has_crls' => $hasCrl,
            'vri_hash' => $vriHash,
            'has_expected_vri_hash' => $expectedVriHash === null
                ? $vriHash !== null
                : strtoupper($expectedVriHash) === $vriHash,
            'offline_validation_ready' => $hasDssReference
                && $hasDssDictionary
                && $vriHash !== null
                && $hasCerts
                && ($hasOcsp || $hasCrl),
            'complete_lt_material' => $hasDssReference
                && $hasDssDictionary
                && $vriHash !== null
                && $hasCerts
                && $hasOcsp
                && $hasCrl,
        ];
    }

    private function firstVriHash(string $pdfContent): ?string
    {
        if (preg_match('/\/VRI\s*<<\s*\/([0-9A-F]{40,128})\s*<</s', $pdfContent, $matches) !== 1) {
            return null;
        }

        return strtoupper($matches[1]);
    }
}
