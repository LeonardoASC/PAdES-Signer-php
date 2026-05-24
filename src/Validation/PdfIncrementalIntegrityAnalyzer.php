<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Pdf\EmbeddedPdfSignature;
use NihilLabs\Pades\Pdf\EmbeddedPdfSignatureExtractor;
use NihilLabs\Pades\Pdf\PdfDictionaryReader;

final readonly class PdfIncrementalIntegrityAnalyzer
{
    public function analyze(string $pdfContent): PdfIncrementalIntegrityReport
    {
        $signatures = (new EmbeddedPdfSignatureExtractor())->extractAll($pdfContent);
        $changes = [];
        $messages = [];
        $hasSuspiciousReplacement = false;
        $hasDisallowedChange = false;
        $xrefCoverageValid = $this->hasValidXrefChain($pdfContent);

        foreach ($signatures as $signature) {
            foreach ($this->changesAfterSignature($pdfContent, $signature) as $change) {
                $changes[] = $change;

                if ($change->redefinesSignedObject && ! $change->allowed) {
                    $hasSuspiciousReplacement = true;
                }

                if (! $change->allowed) {
                    $hasDisallowedChange = true;
                    $messages[] = $change->reason;
                }
            }
        }

        if (! $xrefCoverageValid) {
            $messages[] = 'Cadeia /Prev ou startxref das revisoes incrementais invalida.';
        }

        $checks = [
            'revision_diff_policy' => true,
            'changes_classified' => $changes !== [] || count($signatures) === 1,
            'doc_mdp_allowed' => ! $hasDisallowedChange,
            'field_mdp_allowed' => ! $hasDisallowedChange,
            'dss_and_timestamp_updates_allowed' => $this->hasAllowedValidationUpdates($changes) || $changes === [],
            'signed_objects_not_replaced' => ! $hasSuspiciousReplacement,
            'malicious_incremental_replacement_not_detected' => ! $hasSuspiciousReplacement,
            'xref_revision_coverage' => $xrefCoverageValid,
        ];

        return new PdfIncrementalIntegrityReport(
            valid: $messages === [],
            changes: $changes,
            checks: $checks,
            messages: array_values(array_unique($messages))
        );
    }

    /**
     * @return list<PdfRevisionChange>
     */
    private function changesAfterSignature(string $pdfContent, EmbeddedPdfSignature $signature): array
    {
        $signedObjects = $this->objectNumbersBefore($pdfContent, $signature->signedRevisionEnd);
        $objects = $this->objectsAfter($pdfContent, $signature->signedRevisionEnd);
        $permission = $this->docMdpPermission($signature->signatureDictionary);
        $fieldMdp = $this->fieldMdpPolicy($signature->signatureDictionary);
        $changes = [];

        foreach ($objects as $object) {
            $classification = $this->classifyObject($object['body']);
            $redefinesSignedObject = isset($signedObjects[$object['number']]);
            $allowed = $this->isAllowed(
                classification: $classification,
                redefinesSignedObject: $redefinesSignedObject,
                body: $object['body'],
                docMdpPermission: $permission,
                fieldMdp: $fieldMdp
            );

            $changes[] = new PdfRevisionChange(
                objectNumber: $object['number'],
                offset: $object['offset'],
                classification: $classification,
                allowed: $allowed,
                redefinesSignedObject: $redefinesSignedObject,
                reason: $allowed
                    ? 'Alteracao incremental permitida.'
                    : "Alteracao incremental proibida no objeto {$object['number']} ({$classification})."
            );
        }

        return $changes;
    }

    /**
     * @return array<int, true>
     */
    private function objectNumbersBefore(string $pdfContent, int $end): array
    {
        $objects = [];

        foreach ($this->objectOccurrences(substr($pdfContent, 0, $end)) as $object) {
            $objects[$object['number']] = true;
        }

        return $objects;
    }

    /**
     * @return list<array{number:int,offset:int,body:string}>
     */
    private function objectsAfter(string $pdfContent, int $start): array
    {
        $objects = [];

        foreach ($this->objectOccurrences(substr($pdfContent, $start)) as $object) {
            $objects[] = [
                'number' => $object['number'],
                'offset' => $object['offset'] + $start,
                'body' => $object['body'],
            ];
        }

        return $objects;
    }

    /**
     * @return list<array{number:int,offset:int,body:string}>
     */
    private function objectOccurrences(string $pdfContent): array
    {
        if (! preg_match_all('/(^|\R)(\d+)\s+(\d+)\s+obj\s*(.*?)\s*endobj/s', $pdfContent, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $objects = [];

        foreach ($matches as $match) {
            $objects[] = [
                'number' => (int) $match[2][0],
                'offset' => $match[2][1],
                'body' => trim($match[4][0]),
            ];
        }

        return $objects;
    }

    private function classifyObject(string $body): string
    {
        if (str_contains($body, '/Type /DSS') || str_contains($body, '/DSS ')) {
            return 'dss';
        }

        if (str_contains($body, '/AcroForm') || str_contains($body, '/Fields') || str_contains($body, '/SigFlags')) {
            return 'form_field';
        }

        if (str_contains($body, '/SubFilter /ETSI.RFC3161')) {
            return 'document_timestamp';
        }

        if (str_contains($body, '/Type /Sig') || str_contains($body, '/FT /Sig')) {
            return 'signature';
        }

        if (str_contains($body, '/Subtype /Widget')) {
            return 'form_field';
        }

        if (str_contains($body, '/Subtype /Annot') || str_contains($body, '/Annots')) {
            return 'annotation';
        }

        return 'content';
    }

    /**
     * @param array{action:string,fields:list<string>}|null $fieldMdp
     */
    private function isAllowed(
        string $classification,
        bool $redefinesSignedObject,
        string $body,
        ?int $docMdpPermission,
        ?array $fieldMdp
    ): bool {
        if ($this->violatesFieldMdp($body, $classification, $fieldMdp)) {
            return false;
        }

        if ($docMdpPermission === 1 && ! in_array($classification, ['dss', 'document_timestamp'], true)) {
            return false;
        }

        if ($docMdpPermission === 2 && ! in_array($classification, ['dss', 'document_timestamp', 'signature', 'form_field', 'annotation'], true)) {
            return false;
        }

        if ($docMdpPermission === 3 && ! in_array($classification, ['dss', 'document_timestamp', 'signature', 'form_field', 'annotation'], true)) {
            return false;
        }

        if ($redefinesSignedObject && ! in_array($classification, ['dss', 'document_timestamp', 'signature', 'form_field', 'annotation'], true)) {
            return false;
        }

        return in_array($classification, ['dss', 'document_timestamp', 'signature', 'form_field', 'annotation'], true)
            || ! $redefinesSignedObject;
    }

    private function docMdpPermission(string $signatureDictionary): ?int
    {
        if (! str_contains($signatureDictionary, '/TransformMethod /DocMDP')) {
            return null;
        }

        return preg_match('/\/TransformMethod\s*\/DocMDP.*?\/P\s+(\d+)/s', $signatureDictionary, $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    /**
     * @return array{action:string,fields:list<string>}|null
     */
    private function fieldMdpPolicy(string $signatureDictionary): ?array
    {
        if (! str_contains($signatureDictionary, '/TransformMethod /FieldMDP')) {
            return null;
        }

        preg_match('/\/Action\s*\/(All|Include|Exclude)\b/', $signatureDictionary, $actionMatch);
        preg_match_all('/\(([^)]*)\)/', $signatureDictionary, $fieldMatches);

        return [
            'action' => $actionMatch[1] ?? 'All',
            'fields' => array_values($fieldMatches[1] ?? []),
        ];
    }

    /**
     * @param array{action:string,fields:list<string>}|null $fieldMdp
     */
    private function violatesFieldMdp(string $body, string $classification, ?array $fieldMdp): bool
    {
        if ($fieldMdp === null || ! in_array($classification, ['signature', 'form_field'], true)) {
            return false;
        }

        if ($fieldMdp['action'] === 'All') {
            return true;
        }

        $fieldName = $this->fieldName($body);

        if ($fieldName === null) {
            return false;
        }

        $listed = in_array($fieldName, $fieldMdp['fields'], true);

        return $fieldMdp['action'] === 'Include'
            ? $listed
            : ! $listed;
    }

    private function fieldName(string $body): ?string
    {
        if (! preg_match('/\/T\s*\(([^)]*)\)/', $body, $matches)) {
            return null;
        }

        return str_replace(['\\)', '\\(', '\\\\'], [')', '(', '\\'], $matches[1]);
    }

    /**
     * @param list<PdfRevisionChange> $changes
     */
    private function hasAllowedValidationUpdates(array $changes): bool
    {
        foreach ($changes as $change) {
            if (in_array($change->classification, ['dss', 'document_timestamp'], true) && $change->allowed) {
                return true;
            }
        }

        return false;
    }

    private function hasValidXrefChain(string $pdfContent): bool
    {
        if (! preg_match_all('/startxref\s+(\d+)\s*%%EOF/s', $pdfContent, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $startXrefs = array_map(static fn(array $match): int => (int) $match[0], $matches[1]);

        foreach ($startXrefs as $startXref) {
            if (substr($pdfContent, $startXref, 4) !== 'xref') {
                return false;
            }
        }

        for ($index = 1; $index < count($startXrefs); $index++) {
            $revisionEnd = $matches[0][$index][1] + strlen($matches[0][$index][0]);
            $revision = substr($pdfContent, $startXrefs[$index], $revisionEnd - $startXrefs[$index]);

            if (! str_contains($revision, "/Prev {$startXrefs[$index - 1]}")) {
                return false;
            }
        }

        return true;
    }
}
