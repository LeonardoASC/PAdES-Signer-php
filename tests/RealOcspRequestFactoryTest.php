<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspRequestFactory;
use PHPUnit\Framework\TestCase;

final class RealOcspRequestFactoryTest extends TestCase
{
    public function test_it_builds_real_ocsp_request(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $request = (
            new RealOcspRequestFactory()
        )->build(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertNotEmpty(
            $request
        );

        $this->assertSame(
            0x30,
            ord($request[0])
        );
    }
}