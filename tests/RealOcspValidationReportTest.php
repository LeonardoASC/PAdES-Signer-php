<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationReport;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationReportTest extends TestCase
{
    public function test_it_generates_real_ocsp_validation_report(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $report = (
            new RealOcspValidationReport()
        )->generate(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertArrayHasKey(
            'summary',
            $report
        );

        $this->assertArrayHasKey(
            'inspection',
            $report
        );

        $this->assertIsArray(
            $report['inspection']
        );
    }
}