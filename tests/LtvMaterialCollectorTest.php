<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvMaterialCollector;
use NihilLabs\Pades\Crypto\Validation\ValidationMaterial;
use PHPUnit\Framework\TestCase;

final class LtvMaterialCollectorTest extends TestCase
{
    public function test_it_converts_validation_material_to_ltv_material(): void
    {
        $validationMaterial = new ValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp'],
            crlsDer: ['crl']
        );

        $ltv = (new LtvMaterialCollector())
            ->collect($validationMaterial);

        $this->assertSame(
            ['cert'],
            $ltv->certificatesDer
        );

        $this->assertSame(
            ['ocsp'],
            $ltv->ocspResponsesDer
        );

        $this->assertSame(
            ['crl'],
            $ltv->crlsDer
        );
    }
}