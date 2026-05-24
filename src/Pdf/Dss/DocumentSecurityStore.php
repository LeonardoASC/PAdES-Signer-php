<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf\Dss;

final readonly class DocumentSecurityStore
{
    /**
     * @param array<string> $certReferences
     * @param array<string> $ocspReferences
     * @param array<string> $crlReferences
     * @param array<string, array{certReferences:array<string>,ocspReferences:array<string>,crlReferences:array<string>}> $vriEntries
     */
    public function __construct(
        public array $certReferences = [],
        public array $ocspReferences = [],
        public array $crlReferences = [],
        public array $vriEntries = []
    ) {}

    public function hasVriFor(string $signatureHash): bool
    {
        return isset($this->vriEntries[strtoupper($signatureHash)]);
    }

    public function vriReferencesMaterial(string $signatureHash): bool
    {
        $entry = $this->vriEntries[strtoupper($signatureHash)] ?? null;

        if ($entry === null) {
            return false;
        }

        return $this->referencesExist($entry['certReferences'], $this->certReferences)
            && $this->referencesExist($entry['ocspReferences'], $this->ocspReferences)
            && $this->referencesExist($entry['crlReferences'], $this->crlReferences);
    }

    public function offlineValidationReady(string $signatureHash): bool
    {
        if (! $this->hasVriFor($signatureHash) || ! $this->vriReferencesMaterial($signatureHash)) {
            return false;
        }

        $entry = $this->vriEntries[strtoupper($signatureHash)];

        return $entry['certReferences'] !== []
            && ($entry['ocspReferences'] !== [] || $entry['crlReferences'] !== []);
    }

    public function completeLtMaterial(string $signatureHash): bool
    {
        if (! $this->hasVriFor($signatureHash) || ! $this->vriReferencesMaterial($signatureHash)) {
            return false;
        }

        $entry = $this->vriEntries[strtoupper($signatureHash)];

        return $entry['certReferences'] !== []
            && $entry['ocspReferences'] !== []
            && $entry['crlReferences'] !== [];
    }

    /**
     * @param array<string> $references
     * @param array<string> $available
     */
    private function referencesExist(array $references, array $available): bool
    {
        if ($references === []) {
            return true;
        }

        foreach ($references as $reference) {
            if (! in_array($reference, $available, true)) {
                return false;
            }
        }

        return true;
    }
}
