<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationReportInspector;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationReportInspectorTest extends TestCase
{
    public function test_it_inspects_real_ocsp_report(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $inspection = (
            new RealOcspValidationReportInspector()
        )->inspect(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertArrayHasKey(
            'has_summary',
            $inspection
        );

        $this->assertArrayHasKey(
            'has_inspection',
            $inspection
        );

        $this->assertArrayHasKey(
            'successful',
            $inspection
        );

        $this->assertArrayHasKey(
            'status',
            $inspection
        );
    }
}