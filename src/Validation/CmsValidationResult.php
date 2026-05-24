<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

final readonly class CmsValidationResult
{
    /**
     * @param array<string, bool> $checks
     * @param list<string> $messages
     */
    public function __construct(
        public bool $valid,
        public array $checks,
        public array $messages,
        public ?string $digestAlgorithm,
        public ?string $signatureAlgorithm
    ) {}
}
