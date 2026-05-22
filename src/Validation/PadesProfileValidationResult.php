<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

final readonly class PadesProfileValidationResult
{
    /**
     * @param array<string, bool> $checks
     * @param array<string> $messages
     */
    public function __construct(
        public string $profile,
        public bool $valid,
        public array $checks,
        public array $messages = []
    ) {}
}
