<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Internal\Crypto\PadesCmsSigner;
use PHPUnit\Framework\TestCase;

final class PadesCmsSignerTest extends TestCase
{
    public function test_it_exposes_certificate(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signer = new PadesCmsSigner($certificate);

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

        $cms = (new PadesCmsSigner($certificate))
            ->signPdfByteRangeData('hello world');

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

    public function test_advanced_cms_is_pades_b_b_ready_by_internal_inspection(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new PadesCmsSigner($certificate))
            ->signPdfByteRangeData('hello world');

        $inspection = (new \NihilLabs\Pades\Crypto\PadesBaselineInspector())
            ->inspect($cms);

        $this->assertTrue(
            $inspection['is_cms_signed_data']
        );

        $this->assertTrue(
            $inspection['has_signing_certificate_v2']
        );

        $this->assertTrue(
            $inspection['is_pades_b_b_ready']
        );
    }
}
