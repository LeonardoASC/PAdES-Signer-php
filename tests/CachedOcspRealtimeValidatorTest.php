<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\CachedOcspRealtimeValidator;
use NihilLabs\Pades\Crypto\Ocsp\OcspRealtimeValidator;
use NihilLabs\Pades\Crypto\Ocsp\OcspResponseCache;
use PHPUnit\Framework\TestCase;

final class CachedOcspRealtimeValidatorTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory =
            sys_get_temp_dir()
            . '/pades-ocsp-cache';
    }

    public function test_it_uses_cached_ocsp_response(): void
    {
        $cache = new OcspResponseCache(
            $this->cacheDirectory
        );

        $basic = "\x30\x01\xA0";

        $response =
            "\x30\x14"
            . "\x0A\x01\x00"
            . hex2bin('2B0601050507300101')
            . "\x04"
            . chr(strlen($basic))
            . $basic;

        $cache->put(
            'certificate',
            $response
        );

        $validator = new CachedOcspRealtimeValidator(
            validator: new OcspRealtimeValidator(),
            cache: $cache
        );

        $result = $validator->validate(
            cacheKey: 'certificate',
            certificatePem: 'certificate',
            issuerNameDer: 'issuer',
            issuerPublicKeyDer: 'issuer-key',
            serialNumberHex: '01'
        );

        $this->assertTrue(
            $result->isGood()
        );
    }
}