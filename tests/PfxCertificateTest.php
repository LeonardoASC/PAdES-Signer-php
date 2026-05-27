<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Certificate\PfxCertificateImporter;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use PHPUnit\Framework\TestCase;

final class PfxCertificateTest extends TestCase
{
    public function test_it_loads_a_pfx_certificate(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $this->assertNotNull(
            $certificate->getCommonName()
        );
    }
    public function test_it_gets_serial_number_hex(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $serial = $certificate->getSerialNumberHex();

        $this->assertNotEmpty($serial);

        $this->assertMatchesRegularExpression(
            '/^[0-9A-F]+$/',
            $serial
        );
    }

    public function test_it_loads_a_pfx_certificate_from_contents(): void
    {
        $contents = file_get_contents(__DIR__ . '/Fixtures/certificate.pfx');

        $this->assertNotFalse($contents);

        $certificate = PfxCertificate::fromContents(
            contents: $contents,
            password: '123456'
        );

        $this->assertNotNull($certificate->getCommonName());
        $this->assertNotEmpty($certificate->getPublicCertificate());
    }

    public function test_it_inspects_pfx_contents_for_import_metadata(): void
    {
        $contents = file_get_contents(__DIR__ . '/Fixtures/certificate.pfx');

        $this->assertNotFalse($contents);

        $metadata = (new PfxCertificateImporter())->inspectContents(
            contents: $contents,
            password: '123456'
        );

        $this->assertNotEmpty($metadata->serialNumberHex);
        $this->assertNotEmpty($metadata->certificatePem);
        $this->assertNotEmpty($metadata->certificateChainPem);
        $this->assertInstanceOf(\DateTimeImmutable::class, $metadata->validFrom);
        $this->assertInstanceOf(\DateTimeImmutable::class, $metadata->validTo);
    }

    public function test_it_creates_signature_credential_from_stored_pfx_contents(): void
    {
        $contents = file_get_contents(__DIR__ . '/Fixtures/certificate.pfx');

        $this->assertNotFalse($contents);

        $credential = PfxSignatureCredential::fromContents(
            contents: $contents,
            password: '123456'
        );

        $this->assertNotEmpty($credential->getCertificatePem());
        $this->assertNotEmpty($credential->getCertificateChainPem());
    }
}
