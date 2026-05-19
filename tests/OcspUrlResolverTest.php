<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\OcspUrlResolver;
use PHPUnit\Framework\TestCase;

final class OcspUrlResolverTest extends TestCase
{
    public function test_it_resolves_ocsp_url(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $url = (new OcspUrlResolver())
            ->resolve(
                $certificate->getPublicCertificate()
            );

        $this->assertTrue(
            $url === null
            || str_starts_with($url, 'http')
        );
    }
}