<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class CertificateChainValidationResult
{
    /**
     * @param array<string> $chainPem
     * @param array<string> $messages
     */
    public function __construct(
        public bool $trusted,
        public array $chainPem,
        public ?string $trustAnchorPem = null,
        public array $messages = []
    ) {}
}
