<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationStatus;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationStatusTest extends TestCase
{
    public function test_it_returns_real_ocsp_status(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $status = (
            new RealOcspValidationStatus()
        )->status(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertContains(
            $status,
            [
                'good',
                'revoked',
                'unknown',
                'unavailable',
            ]
        );
    }
}