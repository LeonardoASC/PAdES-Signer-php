<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use PHPUnit\Framework\TestCase;

final class LtvValidationMaterialTest extends TestCase
{
    public function test_it_detects_validation_material_presence(): void
    {
        $material = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp']
        );

        $this->assertTrue(
            $material->hasCertificates()
        );

        $this->assertTrue(
            $material->hasRevocationData()
        );
    }

    public function test_it_detects_empty_validation_material(): void
    {
        $material = new LtvValidationMaterial();

        $this->assertFalse(
            $material->hasCertificates()
        );

        $this->assertFalse(
            $material->hasRevocationData()
        );
    }
}