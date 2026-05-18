<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Asn1;

final readonly class Der
{
    public static function length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $hex = str_pad(dechex($length), 2, '0', STR_PAD_LEFT);

        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        $bytes = hex2bin($hex);

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    public static function sequence(string $content): string
    {
        return "\x30" . self::length(strlen($content)) . $content;
    }

    public static function set(string $content): string
    {
        return "\x31" . self::length(strlen($content)) . $content;
    }

    public static function octetString(string $content): string
    {
        return "\x04" . self::length(strlen($content)) . $content;
    }

    public static function oid(string $hexEncodedOid): string
    {
        $body = hex2bin($hexEncodedOid);

        return "\x06" . self::length(strlen($body)) . $body;
    }

    public static function null(): string
    {
        return "\x05\x00";
    }

    public static function utcTime(string $time): string
    {
        return "\x17" . self::length(strlen($time)) . $time;
    }
}
