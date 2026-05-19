<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationDiagnostics;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationDiagnosticsTest extends TestCase
{
    public function test_it_generates_real_ocsp_diagnostics(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $diagnostics = (
            new RealOcspValidationDiagnostics()
        )->diagnose(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertArrayHasKey(
            'healthy',
            $diagnostics
        );

        $this->assertArrayHasKey(
            'available',
            $diagnostics
        );

        $this->assertArrayHasKey(
            'status',
            $diagnostics
        );

        $this->assertArrayHasKey(
            'successful',
            $diagnostics
        );

        $this->assertArrayHasKey(
            'has_summary',
            $diagnostics
        );

        $this->assertArrayHasKey(
            'has_inspection',
            $diagnostics
        );
    }
}