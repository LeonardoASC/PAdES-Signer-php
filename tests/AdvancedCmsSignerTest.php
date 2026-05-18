<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\CmsSignerInterface;
use PHPUnit\Framework\TestCase;

final class AdvancedCmsSignerTest extends TestCase
{
    public function test_it_implements_cms_signer_interface(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signer = new AdvancedCmsSigner($certificate);

        $this->assertInstanceOf(
            CmsSignerInterface::class,
            $signer
        );
    }

    public function test_it_exposes_certificate(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signer = new AdvancedCmsSigner($certificate);

        $this->assertSame(
            $certificate,
            $signer->getCertificate()
        );
    }

    public function test_it_generates_advanced_cms_der(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new AdvancedCmsSigner($certificate))
            ->signDetachedDer('hello world');

        $this->assertNotEmpty($cms);

        $this->assertStringStartsWith(
            "\x30",
            $cms
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010702'),
            $cms
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010910022f'),
            $cms
        );
    }
}
