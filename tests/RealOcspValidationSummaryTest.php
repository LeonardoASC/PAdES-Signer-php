<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationSummary;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationSummaryTest extends TestCase
{
    public function test_it_summarizes_real_ocsp_validation(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $summary = (
            new RealOcspValidationSummary()
        )->summarize(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertNotEmpty(
            $summary
        );

        $this->assertStringContainsString(
            'successful=',
            $summary
        );

        $this->assertStringContainsString(
            'status=',
            $summary
        );
    }
}