<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

final readonly class PdfIncrementalIntegrityReport
{
    /**
     * @param list<PdfRevisionChange> $changes
     * @param array<string, bool> $checks
     * @param list<string> $messages
     */
    public function __construct(
        public bool $valid,
        public array $changes,
        public array $checks,
        public array $messages
    ) {}
}
