<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Asn1\Der;
use PHPUnit\Framework\TestCase;

final class DerTest extends TestCase
{
    public function test_it_encodes_short_length(): void
    {
        $this->assertSame(
            "\x05",
            Der::length(5)
        );
    }

    public function test_it_encodes_sequence(): void
    {
        $encoded = Der::sequence("\x01\x02");

        $this->assertSame(
            "\x30\x02\x01\x02",
            $encoded
        );
    }

    public function test_it_encodes_sha256_oid(): void
    {
        $encoded = Der::oid('608648016503040201');

        $this->assertSame(
            "\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x01",
            $encoded
        );
    }

    public function test_it_encodes_sha256_algorithm_identifier_without_parameters(): void
    {
        $this->assertSame(
            hex2bin('300b0609608648016503040201'),
            Der::sha256AlgorithmIdentifier()
        );
    }
}
