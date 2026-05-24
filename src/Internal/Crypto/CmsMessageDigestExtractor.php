<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

use RuntimeException;

final readonly class CmsMessageDigestExtractor
{
    private const string MESSAGE_DIGEST_OID = '1.2.840.113549.1.9.4';

    public function extract(string $cmsDer): string
    {
        $attribute = (new CmsSignedDataParser())
            ->parse($cmsDer)
            ->signedAttribute(self::MESSAGE_DIGEST_OID);

        if ($attribute === null) {
            throw new RuntimeException('Atributo CMS messageDigest nao encontrado.');
        }

        $values = (new \NihilLabs\Pades\Crypto\Asn1\DerReader())->children($attribute->valuesEncoded);
        $digest = $values[0] ?? null;

        if ($digest === null || $digest['tag'] !== 0x04) {
            throw new RuntimeException('Atributo CMS messageDigest invalido.');
        }

        return $digest['content'];
    }

    public function digestAlgorithmForLength(int $length): ?string
    {
        return match ($length) {
            32 => 'sha256',
            48 => 'sha384',
            64 => 'sha512',
            default => null,
        };
    }

}
