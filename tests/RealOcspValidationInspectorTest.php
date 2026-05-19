<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationInspector;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationInspectorTest extends TestCase
{
    public function test_it_inspects_real_ocsp_validation(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $inspection = (
            new RealOcspValidationInspector()
        )->inspect(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertArrayHasKey(
            'successful',
            $inspection
        );

        $this->assertArrayHasKey(
            'good',
            $inspection
        );

        $this->assertArrayHasKey(
            'revoked',
            $inspection
        );

        $this->assertArrayHasKey(
            'unknown',
            $inspection
        );

        $this->assertArrayHasKey(
            'certificate_status',
            $inspection
        );
    }
}