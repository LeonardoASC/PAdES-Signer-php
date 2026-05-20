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

    public function test_it_encodes_sha256_algorithm_identifier_with_null_parameters(): void
    {
        $this->assertSame(
            hex2bin('300d06096086480165030402010500'),
            Der::sha256AlgorithmIdentifier()
        );
    }

    public function test_it_sorts_der_set_content_lexicographically(): void
    {
        $late = "\x30\x03\x06\x01\x7F";
        $early = "\x30\x03\x06\x01\x01";
        $middle = "\x30\x03\x06\x01\x10";

        $this->assertSame(
            $early . $middle . $late,
            Der::sortedSetContent([$late, $early, $middle])
        );
    }
}
