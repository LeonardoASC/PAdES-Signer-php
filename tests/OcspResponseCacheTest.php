<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspResponseCache;
use PHPUnit\Framework\TestCase;

final class OcspResponseCacheTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory =
            sys_get_temp_dir()
            . '/pades-ocsp-cache';
    }

    public function test_it_stores_and_loads_ocsp_response(): void
    {
        $cache = new OcspResponseCache(
            $this->cacheDirectory
        );

        $cache->put(
            'certificate-key',
            'ocsp-response'
        );

        $loaded = $cache->get(
            'certificate-key'
        );

        $this->assertSame(
            'ocsp-response',
            $loaded
        );
    }

    public function test_it_returns_null_for_missing_cache(): void
    {
        $cache = new OcspResponseCache(
            $this->cacheDirectory
        );

        $this->assertNull(
            $cache->get('missing')
        );
    }
}