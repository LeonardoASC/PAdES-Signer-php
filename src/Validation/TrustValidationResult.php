<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

final readonly class TrustValidationResult
{
    /**
     * @param array<string> $messages
     */
    public function __construct(
        public bool $trusted,
        public array $messages = []
    ) {}
}
