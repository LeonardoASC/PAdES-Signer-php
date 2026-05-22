<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

final readonly class Rfc3161TimestampValidationResult
{
    /**
     * @param array<string> $messages
     */
    public function __construct(
        public bool $valid,
        public ?Rfc3161TimestampTokenInfo $info = null,
        public array $messages = []
    ) {}
}
