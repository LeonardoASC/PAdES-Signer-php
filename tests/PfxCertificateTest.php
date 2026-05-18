<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
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
}