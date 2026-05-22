<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use NihilLabs\Pades\Crypto\X509\CertificateFormatNormalizer;
use RuntimeException;

final readonly class Rfc3161TimestampTokenParser
{
    public function parseResponse(string $responseDer): Rfc3161TimestampTokenInfo
    {
        $reader = new DerReader();
        $response = $reader->children($responseDer);

        if ($response === [] || $response[0]['tag'] !== 0x30) {
            throw new RuntimeException('TimeStampResp invalido.');
        }

        $status = $this->parseStatus($response[0]['encoded']);

        if (! isset($response[1])) {
            throw new RuntimeException('TimeStampToken nao encontrado.');
        }

        return $this->parseToken($response[1]['encoded'], $status);
    }

    public function parseToken(string $tokenDer, int $status = 0): Rfc3161TimestampTokenInfo
    {
        $reader = new DerReader();
        $contentInfo = $reader->children($tokenDer);

        if (count($contentInfo) < 2 || $contentInfo[0]['tag'] !== 0x06) {
            throw new RuntimeException('TimeStampToken ContentInfo invalido.');
        }

        if ($this->oid($contentInfo[0]['content']) !== '1.2.840.113549.1.7.2') {
            throw new RuntimeException('TimeStampToken nao contem SignedData.');
        }

        $signedData = $this->signedData($contentInfo[1]['encoded']);
        $tstInfo = $this->tstInfo($signedData);

        return new Rfc3161TimestampTokenInfo(
            status: $status,
            policyOid: $tstInfo['policyOid'],
            hashAlgorithmOid: $tstInfo['hashAlgorithmOid'],
            hashedMessage: $tstInfo['hashedMessage'],
            genTime: $tstInfo['genTime'],
            nonce: $tstInfo['nonce'],
            certificatesPem: $this->certificates($signedData)
        );
    }

    private function parseStatus(string $statusDer): int
    {
        $reader = new DerReader();
        $statusInfo = $reader->children($statusDer);

        if ($statusInfo === [] || $statusInfo[0]['tag'] !== 0x02) {
            throw new RuntimeException('PKIStatusInfo invalido.');
        }

        return $this->integer($statusInfo[0]['content']);
    }

    /**
     * @return array<int, array{tag:int,length:int,start:int,contentStart:int,end:int,encoded:string,content:string}>
     */
    private function signedData(string $explicitSignedData): array
    {
        $reader = new DerReader();
        $explicit = $reader->children($explicitSignedData);

        if ($explicit === [] || $explicit[0]['tag'] !== 0x30) {
            throw new RuntimeException('SignedData invalido.');
        }

        return $reader->children($explicit[0]['encoded']);
    }

    /**
     * @param array<int, array{tag:int,encoded:string,content:string}> $signedData
     * @return array{policyOid:string,hashAlgorithmOid:string,hashedMessage:string,genTime:\DateTimeImmutable,nonce:?string}
     */
    private function tstInfo(array $signedData): array
    {
        $reader = new DerReader();
        $encapContentInfo = $reader->children($signedData[2]['encoded'] ?? '');

        if (count($encapContentInfo) < 2 || $this->oid($encapContentInfo[0]['content']) !== '1.2.840.113549.1.9.16.1.4') {
            throw new RuntimeException('TSTInfo nao encontrado no TimeStampToken.');
        }

        $explicitContent = $reader->childrenFromContent($encapContentInfo[1]['content']);
        $octet = $explicitContent[0] ?? null;

        if ($octet === null || $octet['tag'] !== 0x04) {
            throw new RuntimeException('Conteudo TSTInfo invalido.');
        }

        $tstInfo = $reader->children($octet['content']);

        if (count($tstInfo) < 5) {
            throw new RuntimeException('TSTInfo incompleto.');
        }

        $messageImprint = $reader->children($tstInfo[2]['encoded']);
        $algorithm = $reader->children($messageImprint[0]['encoded']);

        return [
            'policyOid' => $this->oid($tstInfo[1]['content']),
            'hashAlgorithmOid' => $this->oid($algorithm[0]['content']),
            'hashedMessage' => $messageImprint[1]['content'],
            'genTime' => $this->generalizedTime($tstInfo[4]['content']),
            'nonce' => isset($tstInfo[5]) && $tstInfo[5]['tag'] === 0x02
                ? $tstInfo[5]['content']
                : null,
        ];
    }

    /**
     * @param array<int, array{tag:int,encoded:string,content:string}> $signedData
     * @return array<string>
     */
    private function certificates(array $signedData): array
    {
        $normalizer = new CertificateFormatNormalizer();

        foreach ($signedData as $field) {
            if ($field['tag'] !== 0xA0) {
                continue;
            }

            $certificates = [];

            foreach ((new DerReader())->childrenFromContent($field['content']) as $certificate) {
                if ($certificate['tag'] !== 0x30) {
                    continue;
                }

                $certificates[] = $normalizer->normalizeToPem($certificate['encoded']);
            }

            return $certificates;
        }

        return [];
    }

    private function generalizedTime(string $value): \DateTimeImmutable
    {
        $normalized = rtrim($value, 'Z');
        $format = str_contains($normalized, '.')
            ? '!YmdHis.u'
            : '!YmdHis';

        $time = \DateTimeImmutable::createFromFormat(
            $format,
            $normalized,
            new \DateTimeZone('UTC')
        );

        if ($time === false) {
            throw new RuntimeException('genTime RFC 3161 invalido.');
        }

        return $time->setTimezone(new \DateTimeZone('UTC'));
    }

    private function integer(string $content): int
    {
        $value = 0;

        foreach (unpack('C*', $content) ?: [] as $byte) {
            $value = ($value << 8) | $byte;
        }

        return $value;
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
