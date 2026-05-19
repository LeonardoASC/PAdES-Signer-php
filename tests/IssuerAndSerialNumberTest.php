<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\IssuerAndSerialNumber;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;
use PHPUnit\Framework\TestCase;

final class IssuerAndSerialNumberTest extends TestCase
{
    public function test_it_builds_issuer_and_serial_number(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $encoded = (new IssuerAndSerialNumber())
            ->build($certificate);

        $this->assertNotEmpty($encoded);

        $this->assertStringStartsWith(
            "\x30",
            $encoded
        );
    }

    public function test_it_contains_certificate_serial_number(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $encoded = (new IssuerAndSerialNumber())
            ->build($certificate);

        $this->assertStringContainsString(
            hex2bin($certificate->getSerialNumberHex()),
            $encoded
        );
    }

    public function test_it_uses_exact_issuer_and_serial_der_from_certificate(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $extractor = new X509NameDerExtractor();
        $certificatePem = $certificate->getPublicCertificate();

        $encoded = (new IssuerAndSerialNumber())
            ->build($certificate);

        $this->assertSame(
            Der::sequence(
                $extractor->extractIssuerNameDer($certificatePem)
                    . $extractor->extractSerialNumberDer($certificatePem)
            ),
            $encoded
        );
    }
}
