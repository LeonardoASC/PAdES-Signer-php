<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\SigningCertificateV2;
use PHPUnit\Framework\TestCase;

final class SigningCertificateV2Test extends TestCase
{
    public function test_it_builds_signing_certificate_v2_structure(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $encoded = (new SigningCertificateV2())
            ->build(
                $certificate->getPublicCertificate()
            );

        $this->assertNotEmpty($encoded);

        $this->assertStringStartsWith(
            "\x30",
            $encoded
        );
    }

    public function test_it_builds_signing_certificate_v2_attribute(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $attribute = (new SigningCertificateV2())
            ->attribute(
                $certificate->getPublicCertificate()
            );

        $this->assertNotEmpty($attribute);

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010910022f'),
            $attribute
        );
    }
}