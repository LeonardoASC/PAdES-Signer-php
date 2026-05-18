<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;

final readonly class ByteRange
{
    public function __construct(
        public int $start1,
        public int $length1,
        public int $start2,
        public int $length2,
    ) {
        if ($start1 < 0 || $length1 < 0 || $start2 < 0 || $length2 < 0) {
            throw new InvalidArgumentException('ByteRange não pode conter valores negativos.');
        }
    }

    public function toPdfArray(): string
    {
        return sprintf(
            '[%d %d %d %d]',
            $this->start1,
            $this->length1,
            $this->start2,
            $this->length2
        );
    }
}