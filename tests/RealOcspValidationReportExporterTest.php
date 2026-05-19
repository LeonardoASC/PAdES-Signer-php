<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationReportExporter;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationReportExporterTest extends TestCase
{
    public function test_it_exports_real_ocsp_report(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $path =
            sys_get_temp_dir()
            . '/real-ocsp-export.json';

        (
            new RealOcspValidationReportExporter()
        )->export(
            path: $path,
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertFileExists(
            $path
        );

        $content = file_get_contents(
            $path
        );

        $this->assertNotFalse(
            $content
        );

        $this->assertStringContainsString(
            '"summary"',
            $content
        );

        $this->assertStringContainsString(
            '"inspection"',
            $content
        );
    }
}