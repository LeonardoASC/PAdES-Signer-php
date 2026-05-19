<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;
use PHPUnit\Framework\TestCase;

final class X509NameDerExtractorTest extends TestCase
{
    public function test_it_extracts_issuer_name_der(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $der = (
            new X509NameDerExtractor()
        )->extractIssuerNameDer(
            $certificate->getPublicCertificate()
        );

        $this->assertNotEmpty(
            $der
        );

        $this->assertSame(
            0x30,
            ord($der[0])
        );
    }
}