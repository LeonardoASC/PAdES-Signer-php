<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Validation\ValidationMaterialCollector;
use PHPUnit\Framework\TestCase;

final class ValidationMaterialCollectorTest extends TestCase
{
    public function test_it_collects_certificate_material(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $material = (new ValidationMaterialCollector())
            ->collect($certificate);

        $this->assertCount(
            1,
            $material->certificatesDer
        );

        $this->assertNotEmpty(
            $material->certificatesDer[0]
        );
    }
}