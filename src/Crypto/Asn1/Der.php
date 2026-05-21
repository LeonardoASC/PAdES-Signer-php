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

    /**
     * @param array<string> $encodedValues
     */
    public static function sortedSetContent(array $encodedValues): string
    {
        usort(
            $encodedValues,
            static fn (string $left, string $right): int => strcmp($left, $right)
        );

        return implode('', $encodedValues);
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

    public static function sha256AlgorithmIdentifier(): string
    {
        return self::algorithmIdentifier('608648016503040201', withNull: true);
    }

    public static function algorithmIdentifier(
        string $oidHex,
        bool $withNull = false,
        ?string $parameters = null
    ): string {
        $content = self::oid($oidHex);

        if ($parameters !== null) {
            $content .= $parameters;
        } elseif ($withNull) {
            $content .= self::null();
        }

        return self::sequence($content);
    }

    public static function utcTime(string $time): string
    {
        return "\x17" . self::length(strlen($time)) . $time;
    }

    public static function generalizedTime(string $time): string
    {
        return "\x18" . self::length(strlen($time)) . $time;
    }

    public static function ia5String(string $value): string
    {
        return "\x16" . self::length(strlen($value)) . $value;
    }

    public static function integer(int $value): string
    {
        if ($value === 0) {
            return "\x02\x01\x00";
        }

        $hex = dechex($value);

        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        $bytes = hex2bin($hex);

        if ($bytes !== false && (ord($bytes[0]) & 0x80)) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02" . self::length(strlen($bytes)) . $bytes;
    }

    public static function contextSpecificConstructed(
        int $tag,
        string $content
    ): string {
        return chr(0xA0 + $tag) . self::length(strlen($content)) . $content;
    }

    public static function contextSpecificImplicit(
        int $tag,
        string $content
    ): string {
        return chr(0x80 + $tag) . self::length(strlen($content)) . $content;
    }
    public static function integerFromHex(
        string $hex
    ): string {
        $hex = strtoupper(
            ltrim($hex, '0')
        );

        if ($hex === '') {
            $hex = '00';
        }

        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        $bytes = hex2bin($hex);

        if ($bytes === false) {
            $bytes = "\x00";
        }

        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02"
            . self::length(strlen($bytes))
            . $bytes;
    }

    public static function utf8String(string $value): string
    {
        return "\x0C" . self::length(strlen($value)) . $value;
    }

    public static function printableString(string $value): string
    {
        return "\x13" . self::length(strlen($value)) . $value;
    }

    public static function contextSpecificImplicitFromEncoded(
        int $tag,
        string $encoded
    ): string {
        if ($encoded === '') {
            return chr(0xA0 + $tag) . "\x00";
        }

        $firstLengthByte = ord($encoded[1]);

        if (($firstLengthByte & 0x80) === 0) {
            $lengthBytesCount = 1;
        } else {
            $lengthBytesCount = ($firstLengthByte & 0x7F) + 1;
        }

        $headerLength = 1 + $lengthBytesCount;

        $content = substr($encoded, $headerLength);

        return chr(0xA0 + $tag)
            . self::length(strlen($content))
            . $content;
    }
}
