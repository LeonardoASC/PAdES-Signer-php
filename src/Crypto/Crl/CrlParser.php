<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Crl;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use NihilLabs\Pades\Internal\Crypto\CmsOid;
use RuntimeException;

final readonly class CrlParser
{
    public function parse(string $crlDer): ParsedCrl
    {
        $reader = new DerReader();
        $fields = $reader->children($crlDer);

        if (count($fields) < 3) {
            throw new RuntimeException('CRL DER invalida.');
        }

        $tbs = $fields[0];
        $signatureAlgorithmOid = $this->algorithmOid($fields[1]['encoded']);
        $signature = $this->bitStringValue($fields[2]);
        $tbsFields = $reader->children($tbs['encoded']);
        $cursor = 0;

        if (($tbsFields[$cursor]['tag'] ?? null) === 0x02) {
            $cursor++;
        }

        $cursor++; // signature
        $issuer = $tbsFields[$cursor++];
        $thisUpdate = $this->time($tbsFields[$cursor++] ?? null);
        $nextUpdate = isset($tbsFields[$cursor]) && in_array($tbsFields[$cursor]['tag'], [0x17, 0x18], true)
            ? $this->time($tbsFields[$cursor++])
            : null;
        $revoked = [];

        if (($tbsFields[$cursor]['tag'] ?? null) === 0x30) {
            foreach ($reader->childrenFromContent($tbsFields[$cursor++]['content']) as $revokedCertificate) {
                $revokedFields = $reader->children($revokedCertificate['encoded']);

                if (($revokedFields[0]['tag'] ?? null) === 0x02) {
                    $revoked[] = strtoupper(ltrim(bin2hex($revokedFields[0]['content']), '0'));
                }
            }
        }

        $aki = null;

        foreach (array_slice($tbsFields, $cursor) as $field) {
            if ($field['tag'] === 0xA0) {
                $aki = $this->authorityKeyIdentifier($field['content']);
            }
        }

        return new ParsedCrl(
            tbsCertListDer: $tbs['encoded'],
            issuerNameDer: $issuer['encoded'],
            thisUpdate: $thisUpdate,
            nextUpdate: $nextUpdate,
            signatureAlgorithmOid: $signatureAlgorithmOid,
            signature: $signature,
            revokedSerialNumbersHex: array_values(array_unique($revoked)),
            authorityKeyIdentifier: $aki
        );
    }

    private function authorityKeyIdentifier(string $extensionsDer): ?string
    {
        $reader = new DerReader();
        $outer = $reader->childrenFromContent($extensionsDer);
        $extensions = ($outer[0]['tag'] ?? null) === 0x30 ? $reader->childrenFromContent($outer[0]['content']) : $outer;

        foreach ($extensions as $extension) {
            $fields = $reader->children($extension['encoded']);

            if (($fields[0]['tag'] ?? null) !== 0x06 || CmsOid::decode($fields[0]['content']) !== '2.5.29.35') {
                continue;
            }

            $value = end($fields);

            if (! is_array($value) || $value['tag'] !== 0x04) {
                continue;
            }

            foreach ($reader->childrenFromContent($value['content']) as $akiField) {
                if ($akiField['tag'] === 0x80) {
                    return strtoupper(bin2hex($akiField['content']));
                }
            }
        }

        return null;
    }

    /**
     * @param array{tag:int,content:string}|null $node
     */
    private function time(?array $node): \DateTimeImmutable
    {
        if ($node === null || ! in_array($node['tag'], [0x17, 0x18], true)) {
            throw new RuntimeException('Tempo CRL invalido.');
        }

        $format = $node['tag'] === 0x17 ? 'ymdHis\Z' : 'YmdHis\Z';
        $time = \DateTimeImmutable::createFromFormat($format, $node['content'], new \DateTimeZone('UTC'));

        if (! $time instanceof \DateTimeImmutable) {
            throw new RuntimeException('Tempo CRL nao parseavel.');
        }

        return $time;
    }

    private function algorithmOid(string $encoded): string
    {
        $children = (new DerReader())->children($encoded);
        return isset($children[0]) && $children[0]['tag'] === 0x06 ? CmsOid::decode($children[0]['content']) : '';
    }

    /**
     * @param array{tag:int,content:string} $bitString
     */
    private function bitStringValue(array $bitString): string
    {
        if ($bitString['tag'] !== 0x03 || $bitString['content'] === '') {
            return '';
        }

        return substr($bitString['content'], 1);
    }
}
