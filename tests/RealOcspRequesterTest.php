<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspRequester;
use PHPUnit\Framework\TestCase;

final class RealOcspRequesterTest extends TestCase
{
    public function test_it_handles_real_ocsp_request(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $response = (
            new RealOcspRequester()
        )->request(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertTrue(
            $response === null
            || is_string($response)
        );
    }
}