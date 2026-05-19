<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Crl\CrlUrlResolver;
use PHPUnit\Framework\TestCase;

final class CrlUrlResolverTest extends TestCase
{
    public function test_it_resolves_crl_urls(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $urls = (new CrlUrlResolver())
            ->resolve(
                $certificate->getPublicCertificate()
            );

        $this->assertIsArray($urls);

        foreach ($urls as $url) {
            $this->assertStringStartsWith(
                'http',
                $url
            );
        }
    }
}