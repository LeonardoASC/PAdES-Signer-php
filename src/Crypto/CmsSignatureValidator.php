<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

final readonly class CmsSignatureValidator
{
    public function isDerEncoded(string $binarySignature): bool
    {
        return str_starts_with(
            $binarySignature,
            "\x30"
        );
    }

    public function containsPkcs7SignedData(
        string $binarySignature
    ): bool {
        return str_contains(
            $binarySignature,
            '1.2.840.113549.1.7.2'
        ) || str_contains(
            bin2hex($binarySignature),
            '06092a864886f70d010702'
        );
    }

    public function validate(string $binarySignature): bool
    {
        return $this->isDerEncoded($binarySignature)
            && $this->containsPkcs7SignedData($binarySignature);
    }
}