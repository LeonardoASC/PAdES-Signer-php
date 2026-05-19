<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationReportFactory;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationReportFactoryTest extends TestCase
{
    public function test_it_creates_real_ocsp_report_and_json(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $factory = new RealOcspValidationReportFactory();

        $report = $factory->create(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $json = $factory->createJson(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('inspection', $report);
        $this->assertJson($json);
    }
}