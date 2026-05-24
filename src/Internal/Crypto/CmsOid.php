<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

final readonly class CmsOid
{
    public static function decode(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $bytes = array_map('ord', str_split($content));
        $first = array_shift($bytes);
        $parts = [intdiv($first, 40), $first % 40];
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
