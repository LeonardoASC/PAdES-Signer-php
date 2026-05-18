<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\HashCalculator;
use PHPUnit\Framework\TestCase;

final class HashCalculatorTest extends TestCase
{
    public function test_it_calculates_sha256_hex(): void
    {
        $hash = new HashCalculator();

        $this->assertSame(
            'b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9',
            $hash->sha256Hex('hello world')
        );
    }

    public function test_it_calculates_sha256_binary(): void
    {
        $hash = new HashCalculator();

        $this->assertSame(
            32,
            strlen($hash->sha256('hello world'))
        );
    }
}
