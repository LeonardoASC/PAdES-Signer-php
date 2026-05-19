<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\X509PublicKeyDerExtractor;
use PHPUnit\Framework\TestCase;

final class X509PublicKeyDerExtractorTest extends TestCase
{
    public function test_it_extracts_subject_public_key_der(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $der = (
            new X509PublicKeyDerExtractor()
        )->extractSubjectPublicKeyDer(
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