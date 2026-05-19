<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspCacheKeyGenerator;
use PHPUnit\Framework\TestCase;

final class OcspCacheKeyGeneratorTest extends TestCase
{
    public function test_it_generates_stable_cache_key(): void
    {
        $generator = new OcspCacheKeyGenerator();

        $keyA = $generator->generate(
            issuerNameDer: 'issuer',
            serialNumberHex: '01AB'
        );

        $keyB = $generator->generate(
            issuerNameDer: 'issuer',
            serialNumberHex: '01AB'
        );

        $this->assertSame(
            $keyA,
            $keyB
        );
    }

    public function test_it_generates_different_keys(): void
    {
        $generator = new OcspCacheKeyGenerator();

        $keyA = $generator->generate(
            issuerNameDer: 'issuer-a',
            serialNumberHex: '01'
        );

        $keyB = $generator->generate(
            issuerNameDer: 'issuer-b',
            serialNumberHex: '01'
        );

        $this->assertNotSame(
            $keyA,
            $keyB
        );
    }
}