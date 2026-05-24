<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class RevocationValidationResult
{
    /**
     * @param array<string, bool> $checks
     * @param list<string> $messages
     */
    public function __construct(
        public bool $valid,
        public array $checks,
        public array $messages
    ) {}
}
