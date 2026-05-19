<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\X509CertificateInfoExtractor;
use PHPUnit\Framework\TestCase;

final class X509CertificateInfoExtractorTest extends TestCase
{
    public function test_it_extracts_certificate_info(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $extractor = new X509CertificateInfoExtractor();

        $info = $extractor->extract(
            certificatePem: $certificate->getPublicCertificate(),
            issuerCertificatePem: $certificate->getPublicCertificate()
        );

        $this->assertArrayHasKey(
            'serialNumberHex',
            $info
        );

        $this->assertArrayHasKey(
            'issuerNameDer',
            $info
        );

        $this->assertArrayHasKey(
            'issuerPublicKeyDer',
            $info
        );

        $this->assertNotEmpty(
            $info['serialNumberHex']
        );

        $this->assertNotEmpty(
            $info['issuerNameDer']
        );

        $this->assertNotEmpty(
            $info['issuerPublicKeyDer']
        );
    }
}