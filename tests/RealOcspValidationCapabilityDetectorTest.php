<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationCapabilityDetector;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationCapabilityDetectorTest extends TestCase
{
    public function test_it_detects_real_ocsp_capabilities(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $capabilities = (
            new RealOcspValidationCapabilityDetector()
        )->detect(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertArrayHasKey(
            'has_summary',
            $capabilities
        );

        $this->assertArrayHasKey(
            'has_inspection',
            $capabilities
        );

        $this->assertArrayHasKey(
            'successful',
            $capabilities
        );

        $this->assertArrayHasKey(
            'has_status',
            $capabilities
        );
    }
}