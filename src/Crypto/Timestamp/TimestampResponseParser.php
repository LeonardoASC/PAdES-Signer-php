<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

use RuntimeException;

final readonly class TimestampResponseParser
{
    public function extractToken(
        string $responseDer
    ): string {
        $offset = 0;

        $outer = $this->readHeader($responseDer, $offset);

        if ($outer['tag'] !== 0x30) {
            throw new RuntimeException('TimeStampResp inválido.');
        }

        $statusStart = $offset;

        $this->skipElement($responseDer, $offset);

        $tokenStart = $offset;

        if ($tokenStart >= strlen($responseDer)) {
            throw new RuntimeException('TimeStampToken não encontrado.');
        }

        return substr($responseDer, $tokenStart);
    }

    /**
     * @return array{tag:int,length:int,header_length:int}
     */
    private function readHeader(
        string $data,
        int &$offset
    ): array {
        if (! isset($data[$offset])) {
            throw new RuntimeException('ASN.1 inválido.');
        }

        $start = $offset;

        $tag = ord($data[$offset]);
        $offset++;

        if (! isset($data[$offset])) {
            throw new RuntimeException('ASN.1 length ausente.');
        }

        $lengthByte = ord($data[$offset]);
        $offset++;

        if (($lengthByte & 0x80) === 0) {
            return [
                'tag' => $tag,
                'length' => $lengthByte,
                'header_length' => $offset - $start,
            ];
        }

        $lengthBytesCount = $lengthByte & 0x7F;

        if ($lengthBytesCount === 0) {
            throw new RuntimeException('ASN.1 indefinite length não suportado.');
        }

        $length = 0;

        for ($i = 0; $i < $lengthBytesCount; $i++) {
            if (! isset($data[$offset])) {
                throw new RuntimeException('ASN.1 length inválido.');
            }

            $length = ($length << 8) | ord($data[$offset]);
            $offset++;
        }

        return [
            'tag' => $tag,
            'length' => $length,
            'header_length' => $offset - $start,
        ];
    }

    private function skipElement(
        string $data,
        int &$offset
    ): void {
        $header = $this->readHeader($data, $offset);

        $offset += $header['length'];

        if ($offset > strlen($data)) {
            throw new RuntimeException('ASN.1 elemento ultrapassa o tamanho do buffer.');
        }
    }
}