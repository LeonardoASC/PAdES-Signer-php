<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class ParsedOcspResponse
{
    /**
     * @param list<OcspSingleResponse> $responses
     * @param list<string> $certificatesDer
     */
    public function __construct(
        public int $responseStatus,
        public ?string $basicResponseDer,
        public ?string $tbsResponseDataDer,
        public ?string $signatureAlgorithmOid,
        public ?string $signature,
        public ?\DateTimeImmutable $producedAt,
        public array $responses = [],
        public array $certificatesDer = [],
        public ?string $nonce = null
    ) {}
}
