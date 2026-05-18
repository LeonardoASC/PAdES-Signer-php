<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\IssuerAndSerialNumber;
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

        $info = $certificate->getInfo();

        $serial = (int) ($info['serialNumber'] ?? 1);

        $encoded = (new IssuerAndSerialNumber())
            ->build($certificate);

        $this->assertStringContainsString(
            ltrim(pack('N', $serial), "\x00"),
            $encoded
        );
    }
}