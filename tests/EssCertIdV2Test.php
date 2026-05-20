<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\Cades\EssCertIdV2;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;
use PHPUnit\Framework\TestCase;

final class EssCertIdV2Test extends TestCase
{
    public function test_it_builds_ess_cert_id_v2(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $encoded = (new EssCertIdV2())
            ->build(
                $certificate->getPublicCertificate()
            );

        $this->assertNotEmpty($encoded);

        $this->assertStringStartsWith(
            "\x30",
            $encoded
        );

        $certificateDer = base64_decode(
            preg_replace(
                '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
                '',
                $certificate->getPublicCertificate()
            ),
            true
        );

        $extractor = new X509NameDerExtractor();

        $expectedIssuerSerial = Der::sequence(
            Der::sequence(
                Der::contextSpecificConstructed(
                    4,
                    $extractor->extractIssuerNameDer(
                        $certificate->getPublicCertificate()
                    )
                )
            )
                . $extractor->extractSerialNumberDer(
                    $certificate->getPublicCertificate()
                )
        );

        $this->assertStringContainsString(
            Der::octetString(hash('sha256', $certificateDer, true)),
            $encoded
        );

        $this->assertStringContainsString(
            $expectedIssuerSerial,
            $encoded
        );
    }
}
