<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Asn1;

use RuntimeException;

final readonly class DerReader
{
    /**
     * @return array{
     *     tag:int,
     *     length:int,
     *     start:int,
     *     contentStart:int,
     *     end:int,
     *     encoded:string,
     *     content:string
     * }
     */
    public function readTlv(string $der, int &$offset): array
    {
        $totalLength = strlen($der);

        if ($offset >= $totalLength) {
            throw new RuntimeException('Fim inesperado do DER.');
        }

        $start = $offset;
        $tag = ord($der[$offset++]);

        if ($offset >= $totalLength) {
            throw new RuntimeException('Length DER ausente.');
        }

        $contentLength = $this->readLength($der, $offset);
        $contentStart = $offset;
        $end = $contentStart + $contentLength;

        if ($end > $totalLength) {
            throw new RuntimeException('Conteudo DER truncado.');
        }

        $offset = $end;

        return [
            'tag' => $tag,
            'length' => $contentLength,
            'start' => $start,
            'contentStart' => $contentStart,
            'end' => $end,
            'encoded' => substr($der, $start, $end - $start),
            'content' => substr($der, $contentStart, $contentLength),
        ];
    }

    /**
     * @return array<int, array{
     *     tag:int,
     *     length:int,
     *     start:int,
     *     contentStart:int,
     *     end:int,
     *     encoded:string,
     *     content:string
     * }>
     */
    public function children(string $encoded): array
    {
        $offset = 0;
        $container = $this->readTlv($encoded, $offset);

        if ($offset !== strlen($encoded)) {
            throw new RuntimeException('DER contem bytes excedentes.');
        }

        return $this->childrenFromContent($container['content']);
    }

    /**
     * @return array<int, array{
     *     tag:int,
     *     length:int,
     *     start:int,
     *     contentStart:int,
     *     end:int,
     *     encoded:string,
     *     content:string
     * }>
     */
    public function childrenFromContent(string $content): array
    {
        $children = [];
        $offset = 0;
        $length = strlen($content);

        while ($offset < $length) {
            $children[] = $this->readTlv($content, $offset);
        }

        return $children;
    }

    private function readLength(string $der, int &$offset): int
    {
        $totalLength = strlen($der);
        $first = ord($der[$offset++]);

        if (($first & 0x80) === 0) {
            return $first;
        }

        $lengthBytes = $first & 0x7F;

        if ($lengthBytes === 0) {
            throw new RuntimeException('DER indefinido nao e permitido.');
        }

        if ($offset + $lengthBytes > $totalLength) {
            throw new RuntimeException('Length DER invalido.');
        }

        $length = 0;

        for ($i = 0; $i < $lengthBytes; $i++) {
            $length = ($length << 8) | ord($der[$offset++]);
        }

        return $length;
    }
}
