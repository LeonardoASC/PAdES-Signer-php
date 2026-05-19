<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationHealth;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationHealthTest extends TestCase
{
    public function test_it_checks_real_ocsp_health(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $health = (
            new RealOcspValidationHealth()
        )->check(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertArrayHasKey(
            'healthy',
            $health
        );

        $this->assertArrayHasKey(
            'status',
            $health
        );

        $this->assertArrayHasKey(
            'available',
            $health
        );
    }
}