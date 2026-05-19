<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationReadinessChecker;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationReadinessCheckerTest extends TestCase
{
    public function test_it_detects_real_ocsp_readiness(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $ready = (
            new RealOcspValidationReadinessChecker()
        )->isReady(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertIsBool(
            $ready
        );
    }
}