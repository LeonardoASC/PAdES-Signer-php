<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use NihilLabs\Pades\Internal\Crypto\CmsOid;
use RuntimeException;

final readonly class OcspResponseParser
{
    public function isParsable(string $responseDer): bool
    {
        try {
            $this->parse($responseDer);
            return true;
        } catch (RuntimeException) {
            return $responseDer !== '' && ord($responseDer[0]) === 0x30;
        }
    }

    public function parse(string $responseDer): ParsedOcspResponse
    {
        $reader = new DerReader();
        $children = $reader->children($responseDer);
        $status = $children[0] ?? null;

        if ($status === null || $status['tag'] !== 0x0A || $status['content'] === '') {
            throw new RuntimeException('OCSPResponse sem responseStatus.');
        }

        $responseStatus = ord($status['content'][strlen($status['content']) - 1]);

        if (! isset($children[1]) || $children[1]['tag'] !== 0xA0) {
            return new ParsedOcspResponse($responseStatus, null, null, null, null, null);
        }

        $explicitResponseBytes = $reader->childrenFromContent($children[1]['content']);
        $responseBytesNode = $explicitResponseBytes[0] ?? null;

        if ($responseBytesNode === null || $responseBytesNode['tag'] !== 0x30) {
            throw new RuntimeException('OCSPResponse sem BasicOCSPResponse.');
        }

        $responseBytes = $reader->children($responseBytesNode['encoded']);
        $responseType = $responseBytes[0] ?? null;
        $response = $responseBytes[1] ?? null;

        if (
            $responseType === null
            || $responseType['tag'] !== 0x06
            || CmsOid::decode($responseType['content']) !== '1.3.6.1.5.5.7.48.1.1'
            || $response === null
            || $response['tag'] !== 0x04
        ) {
            throw new RuntimeException('OCSPResponse sem BasicOCSPResponse.');
        }

        return $this->parseBasic($responseStatus, $response['content']);
    }

    public function parseBasic(int $responseStatus, string $basicDer): ParsedOcspResponse
    {
        $reader = new DerReader();
        $basic = $reader->children($basicDer);

        if (count($basic) < 3) {
            throw new RuntimeException('BasicOCSPResponse incompleto.');
        }

        $tbs = $basic[0];
        $algorithm = $basic[1];
        $signature = $basic[2];
        $tbsFields = $reader->children($tbs['encoded']);
        $cursor = 0;

        if (($tbsFields[$cursor]['tag'] ?? null) === 0xA0) {
            $cursor++;
        }

        $cursor++; // responderID
        $producedAt = $this->time($tbsFields[$cursor++] ?? null);
        $responsesNode = $tbsFields[$cursor++] ?? null;
        $responses = [];

        if ($responsesNode !== null && $responsesNode['tag'] === 0x30) {
            foreach ($reader->childrenFromContent($responsesNode['content']) as $singleResponse) {
                $responses[] = $this->singleResponse($singleResponse['encoded']);
            }
        }

        $nonce = null;

        foreach (array_slice($tbsFields, $cursor) as $field) {
            if ($field['tag'] === 0xA1) {
                $nonce = $this->nonceFromExtensions($field['content']);
            }
        }

        $certificates = [];

        if (($basic[3]['tag'] ?? null) === 0xA0) {
            $certs = $reader->childrenFromContent($basic[3]['content']);
            $sequence = $certs[0] ?? null;

            if ($sequence !== null && $sequence['tag'] === 0x30) {
                foreach ($reader->childrenFromContent($sequence['content']) as $certificate) {
                    if ($certificate['tag'] === 0x30) {
                        $certificates[] = $certificate['encoded'];
                    }
                }
            }
        }

        return new ParsedOcspResponse(
            responseStatus: $responseStatus,
            basicResponseDer: $basicDer,
            tbsResponseDataDer: $tbs['encoded'],
            signatureAlgorithmOid: $this->algorithmOid($algorithm['encoded']),
            signature: $this->bitStringValue($signature),
            producedAt: $producedAt,
            responses: $responses,
            certificatesDer: $certificates,
            nonce: $nonce
        );
    }

    private function singleResponse(string $encoded): OcspSingleResponse
    {
        $reader = new DerReader();
        $fields = $reader->children($encoded);
        $certId = $reader->children($fields[0]['encoded']);
        $hashAlgorithm = $reader->children($certId[0]['encoded']);

        return new OcspSingleResponse(
            hashAlgorithmOid: CmsOid::decode($hashAlgorithm[0]['content']),
            issuerNameHash: $certId[1]['content'],
            issuerKeyHash: $certId[2]['content'],
            serialNumberHex: strtoupper(bin2hex(ltrim($certId[3]['content'], "\x00"))),
            certificateStatus: $this->statusFromTag($fields[1]['tag']),
            thisUpdate: $this->time($fields[2]),
            nextUpdate: $this->nextUpdate(array_slice($fields, 3))
        );
    }

    /**
     * @param list<array{tag:int,encoded:string,content:string}> $fields
     */
    private function nextUpdate(array $fields): ?\DateTimeImmutable
    {
        foreach ($fields as $field) {
            if ($field['tag'] !== 0xA0) {
                continue;
            }

            $inner = (new DerReader())->childrenFromContent($field['content']);
            return $this->time($inner[0] ?? null);
        }

        return null;
    }

    private function statusFromTag(int $tag): string
    {
        return match ($tag) {
            0x80, 0xA0 => 'good',
            0xA1 => 'revoked',
            0x82, 0xA2 => 'unknown',
            default => 'unknown',
        };
    }

    /**
     * @param array{tag:int,content:string}|null $node
     */
    private function time(?array $node): \DateTimeImmutable
    {
        if ($node === null || ! in_array($node['tag'], [0x17, 0x18], true)) {
            throw new RuntimeException('Tempo OCSP invalido.');
        }

        $format = $node['tag'] === 0x17 ? 'ymdHis\Z' : 'YmdHis\Z';
        $time = \DateTimeImmutable::createFromFormat($format, $node['content'], new \DateTimeZone('UTC'));

        if (! $time instanceof \DateTimeImmutable) {
            throw new RuntimeException('Tempo OCSP nao parseavel.');
        }

        return $time;
    }

    private function nonceFromExtensions(string $content): ?string
    {
        $reader = new DerReader();
        $extensions = $reader->childrenFromContent($content);
        $sequence = $extensions[0] ?? null;

        if ($sequence !== null && $sequence['tag'] === 0x30) {
            $extensions = $reader->childrenFromContent($sequence['content']);
        }

        foreach ($extensions as $extension) {
            $fields = $reader->children($extension['encoded']);

            if (($fields[0]['tag'] ?? null) !== 0x06 || CmsOid::decode($fields[0]['content']) !== '1.3.6.1.5.5.7.48.1.2') {
                continue;
            }

            $value = end($fields);

            return is_array($value) && $value['tag'] === 0x04 ? $value['content'] : null;
        }

        return null;
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
