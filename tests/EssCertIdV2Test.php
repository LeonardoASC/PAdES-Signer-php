<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\EssCertIdV2;
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

        $this->assertStringContainsString(
            hash(
                'sha256',
                base64_decode(
                    preg_replace(
                        '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
                        '',
                        $certificate->getPublicCertificate()
                    ),
                    true
                ),
                true
            ),
            $encoded
        );
    }
}