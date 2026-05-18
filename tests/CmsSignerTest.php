<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\CmsSigner;
use PHPUnit\Framework\TestCase;

final class CmsSignerTest extends TestCase
{
    public function test_it_generates_a_detached_pkcs7_signature(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signer = new CmsSigner($certificate);

        $cms = $signer->signDetached('PAdES Core CMS test');

        $this->assertNotEmpty($cms);

        $this->assertStringContainsString(
            'application/x-pkcs7-signature',
            $cms
        );
    }

    public function test_it_generates_a_der_detached_pkcs7_signature(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signer = new CmsSigner($certificate);

        $der = $signer->signDetachedDer('PAdES Core CMS DER test');

        $this->assertNotEmpty($der);

        // ASN.1 DER de SignedData normalmente começa com 0x30: SEQUENCE
        $this->assertSame(
            "\x30",
            $der[0]
        );
    }
}
