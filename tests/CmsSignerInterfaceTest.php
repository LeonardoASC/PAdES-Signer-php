<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\CmsSigner;
use NihilLabs\Pades\Crypto\CmsSignerInterface;
use PHPUnit\Framework\TestCase;

final class CmsSignerInterfaceTest extends TestCase
{
    public function test_cms_signer_implements_interface(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signer = new CmsSigner(
            $certificate
        );

        $this->assertInstanceOf(
            CmsSignerInterface::class,
            $signer
        );

        $signature = $signer
            ->signDetachedDer('test');

        $this->assertNotEmpty($signature);
    }
}