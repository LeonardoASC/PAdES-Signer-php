<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\CachedOcspRealtimeValidator;
use NihilLabs\Pades\Crypto\Ocsp\OcspCacheAwareValidator;
use NihilLabs\Pades\Crypto\Ocsp\OcspCacheKeyGenerator;
use NihilLabs\Pades\Crypto\Ocsp\OcspRealtimeValidator;
use NihilLabs\Pades\Crypto\Ocsp\OcspResponseCache;
use PHPUnit\Framework\TestCase;

final class OcspCacheAwareValidatorTest extends TestCase
{
    public function test_it_validates_using_generated_cache_key(): void
    {
        $cacheDirectory =
            sys_get_temp_dir()
            . '/pades-ocsp-cache';

        $cache = new OcspResponseCache(
            $cacheDirectory
        );

        $basic = "\x30\x01\xA0";

        $response =
            "\x30\x14"
            . "\x0A\x01\x00"
            . hex2bin('2B0601050507300101')
            . "\x04"
            . chr(strlen($basic))
            . $basic;

        $keyGenerator = new OcspCacheKeyGenerator();

        $cacheKey = $keyGenerator->generate(
            issuerNameDer: 'issuer',
            serialNumberHex: '01'
        );

        $cache->put(
            $cacheKey,
            $response
        );

        $validator = new OcspCacheAwareValidator(
            validator: new CachedOcspRealtimeValidator(
                validator: new OcspRealtimeValidator(),
                cache: $cache
            ),
            keyGenerator: $keyGenerator
        );

        $result = $validator->validate(
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