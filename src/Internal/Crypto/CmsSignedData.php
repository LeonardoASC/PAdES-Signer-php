<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

final readonly class CmsSignedData
{
    /**
     * @param list<string> $digestAlgorithmOids
     * @param list<string> $certificatesDer
     * @param array<string, CmsSignedAttribute> $signedAttributes
     */
    public function __construct(
        public string $contentTypeOid,
        public array $digestAlgorithmOids,
        public string $encapContentTypeOid,
        public array $certificatesDer,
        public string $signerIdentifierDer,
        public string $signerDigestAlgorithmOid,
        public string $signatureAlgorithmOid,
        public ?string $signatureAlgorithmParameters,
        public array $signedAttributes,
        public bool $hasUnsignedAttributes
    ) {}

    public function signedAttribute(string $oid): ?CmsSignedAttribute
    {
        return $this->signedAttributes[$oid] ?? null;
    }
}
