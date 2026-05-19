<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Asn1\Der;
use PHPUnit\Framework\TestCase;

final class DerIntegerFromHexTest extends TestCase
{
    public function test_it_encodes_integer_from_hex(): void
    {
        $encoded = Der::integerFromHex(
            '46FD0669377C89860DCC7EC2AEC9BFAD17FBCC15'
        );

        $this->assertStringStartsWith(
            "\x02",
            $encoded
        );

        $this->assertStringContainsString(
            hex2bin(
                '46FD0669377C89860DCC7EC2AEC9BFAD17FBCC15'
            ),
            $encoded
        );
    }
}