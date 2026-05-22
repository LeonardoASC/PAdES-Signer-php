<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class CertificateValidationResult
{
    /**
     * @param array<string> $messages
     * @param array<string, mixed> $details
     */
    public function __construct(
        public bool $valid,
        public array $messages = [],
        public array $details = []
    ) {}
}
