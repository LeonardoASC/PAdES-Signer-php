<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf\Dss;

final readonly class PdfDssInspector
{
    public function extract(string $pdfContent): DocumentSecurityStore
    {
        $dssBody = $this->latestDssBody($pdfContent) ?? '';

        return new DocumentSecurityStore(
            certReferences: $this->referencesForKey($dssBody, 'Certs'),
            ocspReferences: $this->referencesForKey($dssBody, 'OCSPs'),
            crlReferences: $this->referencesForKey($dssBody, 'CRLs'),
            vriEntries: $this->vriEntries($dssBody)
        );
    }

    /**
     * @return array<string, bool|string|null>
     */
    public function inspect(string $pdfContent, ?string $expectedVriHash = null): array
    {
        $hasDssReference = preg_match('/\/DSS\s+\d+\s+0\s+R/', $pdfContent) === 1;
        $hasDssDictionary = str_contains($pdfContent, '/Type /DSS');
        $store = $this->extract($pdfContent);
        $vriHash = $expectedVriHash !== null && $store->hasVriFor($expectedVriHash)
            ? strtoupper($expectedVriHash)
            : $this->firstVriHash($pdfContent);
        $hasCerts = $store->certReferences !== [];
        $hasOcsp = $store->ocspReferences !== [];
        $hasCrl = $store->crlReferences !== [];
        $hasExpectedVriHash = $expectedVriHash === null
            ? $vriHash !== null
            : $store->hasVriFor($expectedVriHash);
        $vriReferencesMaterial = $expectedVriHash !== null
            && $store->vriReferencesMaterial($expectedVriHash);

        return [
            'has_dss_reference' => $hasDssReference,
            'has_dss_dictionary' => $hasDssDictionary,
            'has_vri_dictionary' => $vriHash !== null,
            'has_certificates' => $hasCerts,
            'has_ocsp_responses' => $hasOcsp,
            'has_crls' => $hasCrl,
            'vri_hash' => $vriHash,
            'has_expected_vri_hash' => $hasExpectedVriHash,
            'vri_references_validation_material' => $expectedVriHash === null
                ? $vriHash !== null
                : $vriReferencesMaterial,
            'offline_validation_ready' => $expectedVriHash !== null
                && $hasDssReference
                && $hasDssDictionary
                && $store->offlineValidationReady($expectedVriHash),
            'complete_lt_material' => $expectedVriHash !== null
                && $hasDssReference
                && $hasDssDictionary
                && $store->completeLtMaterial($expectedVriHash),
        ];
    }

    private function firstVriHash(string $pdfContent): ?string
    {
        if (preg_match('/\/VRI\s*<<\s*\/([0-9A-F]{40,128})\s*<</s', $pdfContent, $matches) !== 1) {
            return null;
        }

        return strtoupper($matches[1]);
    }

    private function latestDssBody(string $pdfContent): ?string
    {
        $typePosition = strrpos($pdfContent, '/Type /DSS');

        if ($typePosition === false) {
            return null;
        }

        $objectStart = strrpos(substr($pdfContent, 0, $typePosition), 'obj');
        $objectEnd = strpos($pdfContent, 'endobj', $typePosition);

        if ($objectStart === false || $objectEnd === false) {
            return null;
        }

        return substr($pdfContent, $objectStart + 3, $objectEnd - ($objectStart + 3));
    }

    /**
     * @return array<string>
     */
    private function referencesForKey(string $dictionary, string $key): array
    {
        if (preg_match('/\/' . preg_quote($key, '/') . '\s*\[([^\]]*)\]/s', $dictionary, $matches) !== 1) {
            return [];
        }

        preg_match_all('/\d+\s+0\s+R/', $matches[1], $references);

        return $references[0];
    }

    /**
     * @return array<string, array{certReferences:array<string>,ocspReferences:array<string>,crlReferences:array<string>}>
     */
    private function vriEntries(string $dictionary): array
    {
        if (preg_match('/\/VRI\s*<<(.*)>>\s*$/s', $dictionary, $matches) !== 1) {
            return [];
        }

        $entries = [];
        preg_match_all('/\/([0-9A-F]{40,128})\s*<<(.*?)>>/s', $matches[1], $vriMatches, PREG_SET_ORDER);

        foreach ($vriMatches as $entry) {
            $entries[strtoupper($entry[1])] = [
                'certReferences' => $this->referencesForKey($entry[2], 'Cert'),
                'ocspReferences' => $this->referencesForKey($entry[2], 'OCSP'),
                'crlReferences' => $this->referencesForKey($entry[2], 'CRL'),
            ];
        }

        return $entries;
    }
}
