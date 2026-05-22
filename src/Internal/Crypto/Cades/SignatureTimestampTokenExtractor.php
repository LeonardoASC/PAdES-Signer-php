<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class SignatureTimestampTokenExtractor
{
    private const string SIGNATURE_TIMESTAMP_TOKEN_OID = '1.2.840.113549.1.9.16.2.14';

    public function extract(string $cmsDer): string
    {
        $token = $this->findInEncoded($cmsDer);

        if ($token === null) {
            throw new RuntimeException('Atributo signature-time-stamp-token nao encontrado no CMS.');
        }

        return $token;
    }

    private function findInEncoded(string $encoded): ?string
    {
        $reader = new DerReader();

        try {
            $children = $reader->children($encoded);
        } catch (RuntimeException) {
            return null;
        }

        foreach ($children as $child) {
            if ($child['tag'] === 0x30) {
                $token = $this->tokenFromAttribute($child['encoded']);

                if ($token !== null) {
                    return $token;
                }
            }

            if ($this->isConstructed($child['tag'])) {
                $token = $this->findInContent($child['content']);

                if ($token !== null) {
                    return $token;
                }
            }
        }

        return null;
    }

    private function findInContent(string $content): ?string
    {
        $reader = new DerReader();

        try {
            $children = $reader->childrenFromContent($content);
        } catch (RuntimeException) {
            return null;
        }

        foreach ($children as $child) {
            if ($child['tag'] === 0x30) {
                $token = $this->tokenFromAttribute($child['encoded']);

                if ($token !== null) {
                    return $token;
                }
            }

            if ($this->isConstructed($child['tag'])) {
                $token = $this->findInContent($child['content']);

                if ($token !== null) {
                    return $token;
                }
            }
        }

        return null;
    }

    private function tokenFromAttribute(string $attributeDer): ?string
    {
        $reader = new DerReader();

        try {
            $fields = $reader->children($attributeDer);
        } catch (RuntimeException) {
            return null;
        }

        if (count($fields) < 2 || $fields[0]['tag'] !== 0x06 || $fields[1]['tag'] !== 0x31) {
            return null;
        }

        if ($this->oid($fields[0]['content']) !== self::SIGNATURE_TIMESTAMP_TOKEN_OID) {
            return null;
        }

        $values = $reader->childrenFromContent($fields[1]['content']);

        if ($values === []) {
            return null;
        }

        return $values[0]['encoded'];
    }

    private function isConstructed(int $tag): bool
    {
        return ($tag & 0x20) === 0x20;
    }

    private function oid(string $content): string
    {
        $bytes = array_values(unpack('C*', $content) ?: []);

        if ($bytes === []) {
            return '';
        }

        $first = array_shift($bytes);
        $parts = [
            intdiv($first, 40),
            $first % 40,
        ];
        $value = 0;

        foreach ($bytes as $byte) {
            $value = ($value << 7) | ($byte & 0x7F);

            if (($byte & 0x80) === 0) {
                $parts[] = $value;
                $value = 0;
            }
        }

        return implode('.', $parts);
    }
}
