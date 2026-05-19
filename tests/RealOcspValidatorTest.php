<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidator;
use PHPUnit\Framework\TestCase;

final class RealOcspValidatorTest extends TestCase
{
    public function test_it_handles_real_ocsp_validation(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $result = (
            new RealOcspValidator()
        )->validate(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertIsBool(
            $result->successful
        );
    }
}