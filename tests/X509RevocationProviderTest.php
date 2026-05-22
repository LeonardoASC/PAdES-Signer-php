<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\CachedRevocationProvider;
use NihilLabs\Pades\Crypto\X509\CallbackRevocationProvider;
use NihilLabs\Pades\Crypto\X509\RevocationCacheInterface;
use NihilLabs\Pades\Crypto\X509\RevocationMaterial;
use PHPUnit\Framework\TestCase;

final class X509RevocationProviderTest extends TestCase
{
    public function test_it_uses_a_pluggable_revocation_provider(): void
    {
        $provider = new CallbackRevocationProvider(
            fn (array $chain): RevocationMaterial => new RevocationMaterial(
                ocspResponsesDer: ['ocsp-response'],
                crlsDer: ['crl-response']
            )
        );

        $material = $provider->collect(['certificate']);

        $this->assertTrue($material->hasRevocationData());
        $this->assertSame(['ocsp-response'], $material->ocspResponsesDer);
        $this->assertSame(['crl-response'], $material->crlsDer);
    }

    public function test_it_caches_ocsp_and_crl_material_for_a_certificate_chain(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );
        $calls = 0;
        $cache = new class implements RevocationCacheInterface {
            /**
             * @var array<string, string>
             */
            private array $items = [];

            public function get(string $type, string $key): ?string
            {
                return $this->items[$type . ':' . $key] ?? null;
            }

            public function put(string $type, string $key, string $der): void
            {
                $this->items[$type . ':' . $key] = $der;
            }
        };

        $provider = new CachedRevocationProvider(
            provider: new CallbackRevocationProvider(
                function () use (&$calls): RevocationMaterial {
                    $calls++;

                    return new RevocationMaterial(
                        ocspResponsesDer: ['cached-ocsp'],
                        crlsDer: ['cached-crl']
                    );
                }
            ),
            cache: $cache
        );

        $chain = [$certificate->getPublicCertificate()];

        $first = $provider->collect($chain);
        $second = $provider->collect($chain);

        $this->assertSame(1, $calls);
        $this->assertSame($first->ocspResponsesDer, $second->ocspResponsesDer);
        $this->assertSame($first->crlsDer, $second->crlsDer);
    }
}
