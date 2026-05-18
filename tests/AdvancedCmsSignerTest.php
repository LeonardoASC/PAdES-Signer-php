<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\CmsSignerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function test_it_is_not_implemented_yet(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $this->expectException(RuntimeException::class);

        (new AdvancedCmsSigner($certificate))
            ->signDetachedDer('test');
    }
}