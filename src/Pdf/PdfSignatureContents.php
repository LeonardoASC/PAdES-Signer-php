<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;

final readonly class PdfSignatureContents
{
    public function __construct(
        private int $reservedBytes = 8192
    ) {
        if ($this->reservedBytes <= 0) {
            throw new InvalidArgumentException('O espaço reservado precisa ser maior que zero.');
        }
    }

    public function encode(string $derSignature): string
    {
        $hex = strtoupper(bin2hex($derSignature));

        $reservedHexLength = $this->reservedBytes * 2;

        if (strlen($hex) > $reservedHexLength) {
            throw new InvalidArgumentException(
                'A assinatura excede o espaço reservado no PDF.'
            );
        }

        return str_pad($hex, $reservedHexLength, '0');
    }

    public function placeholder(): string
    {
        return str_repeat('0', $this->reservedBytes * 2);
    }
}