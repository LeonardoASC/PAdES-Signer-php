<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Asn1\Der;
use PHPUnit\Framework\TestCase;

final class DerStringTest extends TestCase
{
    public function test_it_encodes_utf8_string(): void
    {
        $encoded = Der::utf8String('pdf-signer-local');

        $this->assertSame(
            "\x0C\x10pdf-signer-local",
            $encoded
        );
    }

    public function test_it_encodes_printable_string(): void
    {
        $encoded = Der::printableString('BR');

        $this->assertSame(
            "\x13\x02BR",
            $encoded
        );
    }
}