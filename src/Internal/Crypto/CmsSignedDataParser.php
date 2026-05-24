<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class CmsSignedDataParser
{
    public function parse(string $cmsDer): CmsSignedData
    {
        $contentInfo = (new DerReader())->children($cmsDer);

        if (count($contentInfo) < 2 || $contentInfo[0]['tag'] !== 0x06) {
            throw new RuntimeException('CMS ContentInfo invalido.');
        }

        $contentTypeOid = CmsOid::decode($contentInfo[0]['content']);

        if ($contentTypeOid !== '1.2.840.113549.1.7.2' || $contentInfo[1]['tag'] !== 0xA0) {
            throw new RuntimeException('CMS nao contem SignedData.');
        }

        $content = (new DerReader())->childrenFromContent($contentInfo[1]['content']);
        $signedDataNode = $content[0] ?? null;

        if ($signedDataNode === null || $signedDataNode['tag'] !== 0x30) {
            throw new RuntimeException('SignedData CMS invalido.');
        }

        $signedData = (new DerReader())->children($signedDataNode['encoded']);
        $digestAlgorithms = $this->digestAlgorithms($signedData[1] ?? null);
        $encapContentType = $this->encapContentType($signedData[2] ?? null);
        $certificates = $this->certificates($signedData);
        $signerInfos = $this->lastSet($signedData);
        $signerInfo = (new DerReader())->childrenFromContent($signerInfos['content'])[0] ?? null;

        if ($signerInfo === null || $signerInfo['tag'] !== 0x30) {
            throw new RuntimeException('SignerInfo CMS invalido.');
        }

        $signerInfoFields = (new DerReader())->children($signerInfo['encoded']);
        $signedAttributes = $this->signedAttributes($signerInfoFields);
        $signatureAlgorithm = $this->signatureAlgorithm($signerInfoFields);

        return new CmsSignedData(
            contentTypeOid: $contentTypeOid,
            digestAlgorithmOids: $digestAlgorithms,
            encapContentTypeOid: $encapContentType,
            certificatesDer: $certificates,
            signerIdentifierDer: ($signerInfoFields[1] ?? null)['content'] ?? '',
            signerDigestAlgorithmOid: $this->algorithmOid($signerInfoFields[2] ?? null),
            signatureAlgorithmOid: $signatureAlgorithm['oid'],
            signatureAlgorithmParameters: $signatureAlgorithm['parameters'],
            signedAttributes: $signedAttributes,
            hasUnsignedAttributes: $this->hasUnsignedAttributes($signerInfoFields)
        );
    }

    /**
     * @param array<int, array{tag:int,encoded:string,content:string}> $signedData
     * @return list<string>
     */
    private function digestAlgorithms(?array $node = null): array
    {
        if ($node === null || $node['tag'] !== 0x31) {
            return [];
        }

        $algorithms = [];

        foreach ((new DerReader())->childrenFromContent($node['content']) as $algorithm) {
            $algorithms[] = $this->algorithmOid($algorithm);
        }

        return $algorithms;
    }

    /**
     * @param array{tag:int,encoded:string,content:string}|null $node
     */
    private function encapContentType(?array $node): string
    {
        if ($node === null || $node['tag'] !== 0x30) {
            return '';
        }

        $children = (new DerReader())->children($node['encoded']);

        return isset($children[0]) && $children[0]['tag'] === 0x06
            ? CmsOid::decode($children[0]['content'])
            : '';
    }

    /**
     * @param array<int, array{tag:int,encoded:string,content:string}> $signedData
     * @return list<string>
     */
    private function certificates(array $signedData): array
    {
        foreach ($signedData as $field) {
            if ($field['tag'] !== 0xA0) {
                continue;
            }

            return array_map(
                static fn(array $certificate): string => $certificate['encoded'],
                (new DerReader())->childrenFromContent($field['content'])
            );
        }

        return [];
    }

    /**
     * @param array<int, array{tag:int,encoded:string,content:string}> $nodes
     * @return array{tag:int,encoded:string,content:string}
     */
    private function lastSet(array $nodes): array
    {
        foreach (array_reverse($nodes) as $node) {
            if ($node['tag'] === 0x31) {
                return $node;
            }
        }

        throw new RuntimeException('SignerInfos CMS nao encontrado.');
    }

    /**
     * @param array<int, array{tag:int,encoded:string,content:string}> $signerInfoFields
     * @return array<string, CmsSignedAttribute>
     */
    private function signedAttributes(array $signerInfoFields): array
    {
        $attributes = [];

        foreach ($signerInfoFields as $field) {
            if ($field['tag'] !== 0xA0) {
                continue;
            }

            foreach ((new DerReader())->childrenFromContent($field['content']) as $attribute) {
                $children = (new DerReader())->children($attribute['encoded']);

                if (($children[0] ?? null) === null || $children[0]['tag'] !== 0x06 || ($children[1] ?? null) === null) {
                    continue;
                }

                $oid = CmsOid::decode($children[0]['content']);
                $attributes[$oid] = new CmsSignedAttribute(
                    oid: $oid,
                    encoded: $attribute['encoded'],
                    valuesEncoded: $children[1]['encoded'],
                    valuesContent: $children[1]['content']
                );
            }
        }

        return $attributes;
    }

    /**
     * @param array<int, array{tag:int,encoded:string,content:string}> $signerInfoFields
     * @return array{oid:string,parameters:?string}
     */
    private function signatureAlgorithm(array $signerInfoFields): array
    {
        foreach ($signerInfoFields as $index => $field) {
            if ($index <= 2 || $field['tag'] !== 0x30) {
                continue;
            }

            return [
                'oid' => $this->algorithmOid($field),
                'parameters' => $this->algorithmParameters($field),
            ];
        }

        return ['oid' => '', 'parameters' => null];
    }

    /**
     * @param array<int, array{tag:int,encoded:string,content:string}> $signerInfoFields
     */
    private function hasUnsignedAttributes(array $signerInfoFields): bool
    {
        foreach ($signerInfoFields as $field) {
            if ($field['tag'] === 0xA1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{tag:int,encoded:string,content:string}|null $node
     */
    private function algorithmOid(?array $node): string
    {
        if ($node === null || $node['tag'] !== 0x30) {
            return '';
        }

        $children = (new DerReader())->children($node['encoded']);

        return isset($children[0]) && $children[0]['tag'] === 0x06
            ? CmsOid::decode($children[0]['content'])
            : '';
    }

    /**
     * @param array{tag:int,encoded:string,content:string} $node
     */
    private function algorithmParameters(array $node): ?string
    {
        $children = (new DerReader())->children($node['encoded']);

        return $children[1]['encoded'] ?? null;
    }
}
